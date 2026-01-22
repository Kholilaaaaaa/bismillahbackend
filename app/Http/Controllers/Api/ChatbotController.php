<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        try {
            // Path ke script Python
            $pythonScript = base_path('scripts/chatbot.py');
            
            // Eksekusi script Python
            $command = escapeshellcmd("python \"{$pythonScript}\" \"" . addslashes($request->question) . "\"");
            $output = shell_exec($command);
            
            // Parse JSON response
            $response = json_decode($output, true);
            
            if (!$response) {
                throw new \Exception('Invalid response from chatbot');
            }
            
            // Simpan ke database jika sukses
            if ($response['status'] === 'success') {
                DB::table('chat_logs')->insert([
                    'question' => $request->question,
                    'answer' => $response['answer'],
                    'source' => 'chatbot',
                    'metadata' => json_encode([
                        'sources' => $response['sources'] ?? [],
                        'timestamp' => $response['timestamp']
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada sistem chatbot',
                'question' => $request->question,
            ], 500);
        }
    }
    
    public function knowledgeBase(Request $request)
    {
        $query = DB::table('chatbot_knowledge')
            ->where('is_active', 1)
            ->select('id', 'question', 'answer', 'category', 'tags', 'created_at');
            
        // Filter berdasarkan kategori jika ada
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Search jika ada
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }
        
        $knowledge = $query->paginate(20);
        
        return response()->json([
            'status' => 'success',
            'data' => $knowledge,
        ]);
    }
    
    public function addKnowledge(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string|max:50',
            'tags' => 'nullable|string|max:255',
        ]);
        
        try {
            $id = DB::table('chatbot_knowledge')->insertGetId([
                'question' => $request->question,
                'answer' => $request->answer,
                'category' => $request->category,
                'tags' => $request->tags,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Trigger rebuild vectorstore
            $pythonScript = base_path('scripts/chatbot.py');
            shell_exec(escapeshellcmd("python \"{$pythonScript}\" --rebuild"));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge berhasil ditambahkan',
                'data' => ['id' => $id],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Add knowledge error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan knowledge',
            ], 500);
        }
    }
}