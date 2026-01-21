<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use App\Http\Controllers\Controller;
use App\Models\ChatbotEmbedding;
use App\Services\EmbeddingService;

class ChatbotController extends Controller
{
    /**
     * Endpoint utama chatbot (RAG)
     */
    public function chat(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:3'
        ]);

        $question = $request->input('question');

        try {
            // =========================
            // 1️⃣ Panggil Python chatbot.py
            // =========================
            $pythonPath = env('PYTHON_PATH', 'python'); // atau full path ke python.exe
            $scriptPath = base_path('scripts/chatbot.py');

            // Aman untuk pertanyaan dengan spasi
            $process = Process::run([
                $pythonPath,
                $scriptPath,
                $question
            ]);

            if (!$process->successful()) {
                throw new \Exception("Python chatbot error: " . $process->errorOutput());
            }

            $output = $process->output();
            $result = json_decode($output, true);

            if (!$result || !isset($result['answer'])) {
                throw new \Exception("Invalid response from Python chatbot");
            }

            return response()->json([
                'status' => $result['status'] ?? 'success',
                'question' => $result['question'] ?? $question,
                'answer' => $result['answer'],
                'timestamp' => $result['timestamp'] ?? now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Throwable $e) {
            Log::error('Chatbot API error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * (Opsional) Embedding via HF API
     * =========================
     */
    private function embedText(string $text): ?array
    {
        $response = Http::withToken(env('HF_API_KEY'))
            ->timeout(60)
            ->post(
                'https://api-inference.huggingface.co/pipeline/feature-extraction/sentence-transformers/all-MiniLM-L6-v2',
                ['inputs' => $text]
            );

        if (!$response->successful()) {
            Log::error('HF embedding failed', [
                'response' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * =========================
     * (Opsional) Generate answer via HF LLM
     * =========================
     */
    private function generateAnswer(string $prompt): string
    {
        $response = Http::withToken(env('HF_API_KEY'))
            ->timeout(90)
            ->post(
                'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2',
                [
                    'inputs' => $prompt,
                    'parameters' => [
                        'temperature' => 0.6,
                        'max_new_tokens' => 300,
                        'return_full_text' => false
                    ]
                ]
            );

        if (!$response->successful()) {
            throw new \Exception("LLM request failed: " . $response->body());
        }

        return $response->json()[0]['generated_text'] ?? '';
    }
}
