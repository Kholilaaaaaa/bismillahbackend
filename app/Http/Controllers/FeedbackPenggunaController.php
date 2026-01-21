<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FeedbackPenggunaController extends Controller
{
    /**
     * Display a listing of the feedback.
     */
    public function index()
    {
        $feedbacks = Feedback::with('pengguna')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $hasAnalysis = false;
        $analysisSummary = null;
        
        try {
            $analysisPath = storage_path('app/sentiment_analysis/results.json');
            if (file_exists($analysisPath)) {
                $analysisData = json_decode(file_get_contents($analysisPath), true);
                if ($analysisData && isset($analysisData['status']) && $analysisData['status'] === 'success') {
                    $hasAnalysis = true;
                    $analysisSummary = [
                        'total_feedback' => $analysisData['summary']['total_feedback'] ?? 0,
                        'positive' => $analysisData['summary']['sentiment_distribution']['positive'] ?? 0,
                        'negative' => $analysisData['summary']['sentiment_distribution']['negative'] ?? 0,
                        'neutral' => $analysisData['summary']['sentiment_distribution']['neutral'] ?? 0,
                        'mrr' => $analysisData['mrr'] ?? 0,
                        'analysis_date' => $analysisData['summary']['analysis_date'] ?? null,
                        'model_algorithm' => $analysisData['model_info']['algorithm'] ?? 'SVM + Rule-based Hybrid',
                        'model_trained' => $analysisData['model_info']['trained'] ?? false
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading analysis data: ' . $e->getMessage());
        }
            
        return view('admins.feedback-pengguna.index', compact('feedbacks', 'hasAnalysis', 'analysisSummary'));
    }

    /**
     * Display the specified feedback.
     */
    public function show($id)
    {
        try {
            $feedback = Feedback::with('pengguna')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $feedback
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Run sentiment analysis with SVM model
     */
    public function analyzeSentiment(Request $request)
    {
        try {
            // Path ke script Python yang sudah dimodifikasi dengan SVM
            $pythonScript = base_path('scripts/sentiment_analysis.py');
            $pythonExecutable = base_path('scripts/venv/Scripts/python.exe');
            
            if (!file_exists($pythonScript)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Script analisis sentimen tidak ditemukan. Pastikan file sentiment_analysis.py ada di folder scripts.'
                ], 404);
            }
            
            if (!file_exists($pythonExecutable)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python executable tidak ditemukan. Silakan jalankan install_python_deps.bat terlebih dahulu.'
                ], 404);
            }
            
            // Periksa apakah ada model SVM yang sudah ada
            $modelsDir = base_path('scripts/models');
            $hasExistingModel = file_exists($modelsDir . '/sentiment_model.pkl') && 
                               file_exists($modelsDir . '/vectorizer.pkl');
            
            Log::info('Memulai analisis sentimen dengan SVM model');
            Log::info('Model yang sudah ada: ' . ($hasExistingModel ? 'Ya' : 'Tidak'));
            
            // Jalankan script Python
            $command = escapeshellcmd("\"{$pythonExecutable}\" \"{$pythonScript}\" 2>&1");
            Log::info('Executing Python command: ' . $command);
            
            // Eksekusi command
            $output = shell_exec($command);
            
            if ($output === null) {
                Log::error('Failed to execute Python script. No output returned.');
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menjalankan script Python. Tidak ada output yang dikembalikan.'
                ], 500);
            }
            
            $outputString = trim($output);
            
            // Log output untuk debugging
            Log::info('Python script output length: ' . strlen($outputString));
            if (strlen($outputString) > 1000) {
                Log::info('First 1000 chars: ' . substr($outputString, 0, 1000));
                Log::info('Last 500 chars: ' . substr($outputString, -500));
            } else {
                Log::info('Full output: ' . $outputString);
            }
            
            // Cek apakah ada error traceback
            if (strpos($outputString, 'Traceback') !== false) {
                $error = $this->extractPythonError($outputString);
                Log::error('Python traceback error: ' . $error);
                return response()->json([
                    'success' => false,
                    'message' => 'Error Python: ' . $error,
                    'type' => 'python_error'
                ], 500);
            }
            
            // Parse JSON output
            $result = $this->parsePythonOutput($outputString);
            
            if (!$result) {
                Log::error('Failed to parse Python output. Raw output length: ' . strlen($outputString));
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses output dari script Python.',
                    'debug' => 'Output tidak valid',
                    'raw_output_sample' => substr($outputString, 0, 500)
                ], 500);
            }
            
            if ($result['status'] === 'error') {
                Log::error('Python script returned error: ' . $result['message']);
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
            
            // Simpan hasil analisis
            $saved = $this->saveAnalysisResults($result);
            
            if (!$saved) {
                Log::warning('Gagal menyimpan hasil analisis, tetapi proses berhasil.');
            }
            
            // Update feedback dengan hasil sentimen jika ada data
            $this->updateFeedbackSentiments($result['data'] ?? []);
            
            Log::info('Sentiment analysis completed successfully. Analyzed: ' . count($result['data']) . ' feedbacks');
            Log::info('SVM Model Info: ' . json_encode($result['model_info'] ?? []));
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'summary' => $result['summary'],
                'mrr' => $result['mrr'],
                'model_info' => $result['model_info'] ?? [
                    'algorithm' => 'SVM (Support Vector Machine) + Rule-based Hybrid',
                    'trained' => false
                ],
                'total_analyzed' => count($result['data']),
                'sentiment_distribution' => $result['summary']['sentiment_distribution'] ?? [],
                'average_rating' => $result['summary']['average_rating'] ?? 0,
                'rating_sentiment_agreement' => $result['summary']['rating_sentiment_agreement'] ?? 0,
                'analysis_methods' => $result['summary']['analysis_methods'] ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('Sentiment analysis error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'type' => 'system_error'
            ], 500);
        }
    }
    
    /**
     * Parse Python output to extract JSON
     */
    private function parsePythonOutput($outputString)
    {
        // Method 1: Coba parse seluruh output sebagai JSON
        $result = json_decode($outputString, true);
        if ($result !== null && isset($result['status'])) {
            return $result;
        }
        
        // Method 2: Cari JSON di dalam output (handle logging)
        $jsonStart = strpos($outputString, '{"status":');
        if ($jsonStart === false) {
            $jsonStart = strpos($outputString, '{"status"');
        }
        
        if ($jsonStart !== false) {
            $jsonEnd = strrpos($outputString, '}') + 1;
            if ($jsonEnd > $jsonStart) {
                $jsonString = substr($outputString, $jsonStart, $jsonEnd - $jsonStart);
                $result = json_decode($jsonString, true);
                if ($result !== null && isset($result['status'])) {
                    return $result;
                }
            }
        }
        
        // Method 3: Coba cari pattern JSON lainnya
        $pattern = '/\{.*"status".*:.*".*".*\}/s';
        if (preg_match($pattern, $outputString, $matches)) {
            $result = json_decode($matches[0], true);
            if ($result !== null && isset($result['status'])) {
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * Extract Python error from traceback
     */
    private function extractPythonError($output)
    {
        $lines = explode("\n", $output);
        $errorLines = [];
        $inTraceback = false;
        
        foreach ($lines as $line) {
            if (strpos($line, 'Traceback') !== false) {
                $inTraceback = true;
                $errorLines = []; // Reset untuk traceback baru
                $errorLines[] = $line;
            } elseif ($inTraceback) {
                $errorLines[] = $line;
                
                // Stop saat menemukan error message utama
                if (preg_match('/^\w+Error:|^\w+Exception:|^Error:|^Exception:/', $line)) {
                    break;
                }
            }
        }
        
        // Ambil hanya bagian penting
        if (count($errorLines) > 10) {
            $errorLines = array_merge(
                array_slice($errorLines, 0, 3),
                ['...'],
                array_slice($errorLines, -3)
            );
        }
        
        return implode(' | ', array_filter($errorLines));
    }
    
    /**
     * Update feedback dengan hasil sentimen
     */
    private function updateFeedbackSentiments($sentimentData)
    {
        try {
            $updated = 0;
            foreach ($sentimentData as $data) {
                if (isset($data['feedback_id']) && isset($data['sentiment'])) {
                    $feedback = Feedback::find($data['feedback_id']);
                    if ($feedback) {
                        $feedback->sentiment = $data['sentiment'];
                        $feedback->sentiment_probability = json_encode($data['probability'] ?? []);
                        $feedback->analysis_method = $data['analysis_method'] ?? 'unknown';
                        $feedback->analyzed_at = now();
                        $feedback->save();
                        $updated++;
                    }
                }
            }
            Log::info("Updated $updated feedback records with sentiment analysis.");
            return $updated;
        } catch (\Exception $e) {
            Log::error('Error updating feedback sentiments: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Simpan hasil analisis
     */
    private function saveAnalysisResults($results)
    {
        try {
            $directory = storage_path('app/sentiment_analysis');
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            
            $filePath = $directory . '/results.json';
            file_put_contents($filePath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Simpan log ke database
            if (isset($results['summary'])) {
                DB::table('sentiment_analysis_logs')->insert([
                    'total_feedback' => $results['summary']['total_feedback'] ?? 0,
                    'positive_count' => $results['summary']['sentiment_distribution']['positive'] ?? 0,
                    'negative_count' => $results['summary']['sentiment_distribution']['negative'] ?? 0,
                    'neutral_count' => $results['summary']['sentiment_distribution']['neutral'] ?? 0,
                    'mrr_score' => $results['mrr'] ?? 0,
                    'model_algorithm' => $results['model_info']['algorithm'] ?? 'SVM + Rule-based Hybrid',
                    'model_trained' => $results['model_info']['trained'] ?? false,
                    'analysis_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Juga simpan backup dengan timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backupPath = $directory . '/results_' . $timestamp . '.json';
            file_put_contents($backupPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            Log::info('Analysis results saved to: ' . $filePath);
            Log::info('Backup saved to: ' . $backupPath);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error saving analysis results: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get sentiment analysis results
     */
    public function getSentimentResults(Request $request)
    {
        try {
            $resultsPath = storage_path('app/sentiment_analysis/results.json');
            
            if (!file_exists($resultsPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil analisis tidak ditemukan.'
                ], 404);
            }
            
            $results = json_decode(file_get_contents($resultsPath), true);
            
            // Paginate results
            $page = $request->get('page', 1);
            $perPage = 10;
            $data = $results['data'] ?? [];
            
            // Calculate pagination
            $total = count($data);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($data, $offset, $perPage);
            
            // Tambahkan data feedback tambahan jika diperlukan
            foreach ($paginatedData as &$item) {
                if (isset($item['feedback_id'])) {
                    $feedback = Feedback::with('pengguna')->find($item['feedback_id']);
                    if ($feedback) {
                        $item['feedback_details'] = [
                            'rating' => $feedback->rating,
                            'review' => $feedback->review,
                            'created_at' => $feedback->created_at,
                            'user_name' => $feedback->pengguna->nama_lengkap ?? 'Unknown',
                            'user_email' => $feedback->pengguna->email ?? ''
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $paginatedData,
                'summary' => $results['summary'] ?? [],
                'model_info' => $results['model_info'] ?? [],
                'mrr' => $results['mrr'] ?? 0,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading sentiment results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat hasil analisis.'
            ], 500);
        }
    }

    /**
     * Get sentiment statistics
     */
    public function getSentimentStats()
    {
        try {
            $resultsPath = storage_path('app/sentiment_analysis/results.json');
            
            if (!file_exists($resultsPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data analisis tidak ditemukan.'
                ], 404);
            }
            
            $results = json_decode(file_get_contents($resultsPath), true);
            
            // Ambil dari summary jika ada
            if (isset($results['summary']['sentiment_distribution'])) {
                $stats = $results['summary']['sentiment_distribution'];
                $stats['total'] = $results['summary']['total_feedback'] ?? array_sum($stats);
            } else {
                // Hitung manual dari data
                $data = $results['data'] ?? [];
                $sentiments = array_column($data, 'sentiment');
                
                $stats = [
                    'total' => count($data),
                    'positive' => 0,
                    'negative' => 0,
                    'neutral' => 0
                ];
                
                foreach ($sentiments as $sentiment) {
                    if (isset($stats[$sentiment])) {
                        $stats[$sentiment]++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'summary' => $results['summary'] ?? [],
                'model_info' => $results['model_info'] ?? [],
                'mrr' => $results['mrr'] ?? 0,
                'analysis_date' => $results['summary']['analysis_date'] ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting sentiment stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik.'
            ], 500);
        }
    }

    /**
     * Get SVM model status
     */
    public function getModelStatus()
    {
        try {
            $modelsDir = base_path('scripts/models');
            $hasModel = file_exists($modelsDir . '/sentiment_model.pkl') && 
                       file_exists($modelsDir . '/vectorizer.pkl');
            
            $modelInfo = [];
            if ($hasModel) {
                $resultsPath = storage_path('app/sentiment_analysis/results.json');
                if (file_exists($resultsPath)) {
                    $results = json_decode(file_get_contents($resultsPath), true);
                    $modelInfo = $results['model_info'] ?? [];
                }
            }
            
            return response()->json([
                'success' => true,
                'has_model' => $hasModel,
                'model_info' => $modelInfo,
                'models_directory' => $modelsDir
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting model status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memeriksa status model.'
            ], 500);
        }
    }

    /**
     * Clear SVM model and retrain
     */
    public function clearAndRetrainModel(Request $request)
    {
        try {
            $modelsDir = base_path('scripts/models');
            $modelFile = $modelsDir . '/sentiment_model.pkl';
            $vectorizerFile = $modelsDir . '/vectorizer.pkl';
            
            $deleted = 0;
            if (file_exists($modelFile)) {
                unlink($modelFile);
                $deleted++;
            }
            if (file_exists($vectorizerFile)) {
                unlink($vectorizerFile);
                $deleted++;
            }
            
            Log::info("Cleared $deleted model files for retraining.");
            
            return response()->json([
                'success' => true,
                'message' => 'Model SVM telah dihapus. Jalankan analisis sentimen untuk melatih model baru.',
                'deleted_files' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing model: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus model: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Python connection
     */
    public function testPythonConnection()
    {
        try {
            $pythonExecutable = base_path('scripts/venv/Scripts/python.exe');
            
            if (!file_exists($pythonExecutable)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python executable tidak ditemukan'
                ]);
            }
            
            $command = escapeshellcmd("\"{$pythonExecutable}\" --version 2>&1");
            $output = shell_exec($command);
            
            // Coba import library untuk test
            $testScript = base_path('scripts/test_imports.py');
            $importTest = '';
            if (file_exists($testScript)) {
                $testCommand = escapeshellcmd("\"{$pythonExecutable}\" \"{$testScript}\" 2>&1");
                $importTest = shell_exec($testCommand);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Python ditemukan',
                'version' => trim($output),
                'import_test' => $importTest ? trim($importTest) : 'Test script tidak ditemukan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate stars HTML for rating
     */
    public function generateStars($rating)
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        return $stars;
    }
}