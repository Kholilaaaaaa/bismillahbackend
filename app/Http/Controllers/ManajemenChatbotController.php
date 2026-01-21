<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;
use App\Models\ChatbotKnowledge;

class ManajemenChatbotController extends Controller
{
    /**
     * =========================
     * 1. DASHBOARD STATISTIK KNOWLEDGE BASE
     * =========================
     */
    public function index(Request $request)
    {
        try {
            $totalQuestions = ChatbotKnowledge::count();
            $totalSources = ChatbotKnowledge::distinct('source')->count('source');
            
            // Hitung pertanyaan per source
            $sourceStats = ChatbotKnowledge::select('source', DB::raw('count(*) as total'))
                ->groupBy('source')
                ->orderBy('total', 'desc')
                ->get();
            
            // Pertanyaan terbaru
            $terbaru = ChatbotKnowledge::orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'question', 'source', 'created_at']);
            
            return response()->json([
                'status' => 'success',
                'total_questions' => $totalQuestions,
                'total_sources' => $totalSources,
                'statistik_source' => $sourceStats,
                'terbaru_ditambahkan' => $terbaru->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'question_preview' => substr($item->question, 0, 100) . '...',
                        'source' => $item->source,
                        'ditambahkan' => $item->created_at->format('d M Y H:i')
                    ];
                })
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Manajemen Chatbot index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data chatbot'
            ], 500);
        }
    }

    /**
     * =========================
     * 2. LIST SEMUA KNOWLEDGE (DataTables compatible)
     * =========================
     */
    public function listKnowledge(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $source = $request->input('source');
            $search = $request->input('search');
            
            $query = ChatbotKnowledge::query()
                ->select(['id', 'question', 'answer', 'source', 'created_at', 'updated_at']);
            
            if ($source) {
                $query->where('source', $source);
            }
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('question', 'like', '%' . $search . '%')
                      ->orWhere('answer', 'like', '%' . $search . '%');
                });
            }
            
            $knowledge = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            // Format response
            return response()->json([
                'status' => 'success',
                'data' => $knowledge->items(),
                'pagination' => [
                    'current_page' => $knowledge->currentPage(),
                    'per_page' => $knowledge->perPage(),
                    'total' => $knowledge->total(),
                    'last_page' => $knowledge->lastPage()
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('List knowledge error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data knowledge'
            ], 500);
        }
    }

    /**
     * =========================
     * 3. TAMPILKAN DETAIL KNOWLEDGE
     * =========================
     */
    public function show($id)
    {
        try {
            $knowledge = ChatbotKnowledge::find($id);
            
            if (!$knowledge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Knowledge tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $knowledge->id,
                    'question' => $knowledge->question,
                    'answer' => $knowledge->answer,
                    'source' => $knowledge->source,
                    'created_at' => $knowledge->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $knowledge->updated_at->format('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Show knowledge error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail knowledge'
            ], 500);
        }
    }

    /**
     * =========================
     * 4. TAMBAH KNOWLEDGE DARI CSV
     * =========================
     */
    public function storeCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            
            // Generate unique filename
            $filename = 'csv_' . time() . '_' . uniqid() . '.csv';
            $path = $file->storeAs('chatbot_csv', $filename, 'local');
            
            // Process CSV file
            $csvPath = storage_path('app/' . $path);
            $csvData = array_map('str_getcsv', file($csvPath));
            
            // Remove header
            $header = array_shift($csvData);
            
            $processedCount = 0;
            foreach ($csvData as $row) {
                if (count($row) >= 2) {
                    ChatbotKnowledge::create([
                        'question' => $row[0],
                        'answer' => $row[1],
                        'source' => 'csv_import'
                    ]);
                    $processedCount++;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'CSV berhasil diproses',
                'data' => [
                    'filename' => $originalName,
                    'rows_processed' => $processedCount,
                    'file_size' => $this->formatBytes($fileSize)
                ]
            ]);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Tambah CSV error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 5. TAMBAH KNOWLEDGE MANUAL
     * =========================
     */
    public function storeManual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:3|max:500',
            'answer' => 'required|string|min:10|max:5000',
            'source' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $knowledge = ChatbotKnowledge::create([
                'question' => $request->input('question'),
                'answer' => $request->input('answer'),
                'source' => $request->input('source', 'manual')
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge berhasil ditambahkan',
                'data' => $knowledge
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Tambah knowledge manual error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan knowledge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 6. UPDATE KNOWLEDGE
     * =========================
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|string|min:3|max:500',
            'answer' => 'sometimes|string|min:10|max:5000',
            'source' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $knowledge = ChatbotKnowledge::find($id);
            
            if (!$knowledge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Knowledge tidak ditemukan'
                ], 404);
            }
            
            // Update fields
            $updateData = [];
            
            if ($request->has('question')) {
                $updateData['question'] = $request->input('question');
            }
            
            if ($request->has('answer')) {
                $updateData['answer'] = $request->input('answer');
            }
            
            if ($request->has('source')) {
                $updateData['source'] = $request->input('source');
            }
            
            $knowledge->update($updateData);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge berhasil diupdate',
                'data' => $knowledge
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Update knowledge error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate knowledge'
            ], 500);
        }
    }

    /**
     * =========================
     * 7. HAPUS KNOWLEDGE
     * =========================
     */
    public function destroy($id)
    {
        try {
            $knowledge = ChatbotKnowledge::find($id);
            
            if (!$knowledge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Knowledge tidak ditemukan'
                ], 404);
            }
            
            $knowledge->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge berhasil dihapus'
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Hapus knowledge error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus knowledge'
            ], 500);
        }
    }

    /**
     * =========================
     * 8. PENCARIAN KNOWLEDGE
     * =========================
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->input('query');
            $limit = $request->input('limit', 10);
            
            $results = ChatbotKnowledge::where('question', 'like', '%' . $query . '%')
                ->orWhere('answer', 'like', '%' . $query . '%')
                ->orWhere('source', 'like', '%' . $query . '%')
                ->limit($limit)
                ->get(['id', 'question', 'answer', 'source', 'created_at']);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'query' => $query,
                    'total_results' => $results->count(),
                    'results' => $results->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'question' => substr($item->question, 0, 100) . (strlen($item->question) > 100 ? '...' : ''),
                            'answer' => substr($item->answer, 0, 100) . (strlen($item->answer) > 100 ? '...' : ''),
                            'source' => $item->source,
                            'created_at' => $item->created_at->format('Y-m-d H:i:s')
                        ];
                    })
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan pencarian'
            ], 500);
        }
    }

    /**
     * =========================
     * 9. BACKUP KNOWLEDGE BASE
     * =========================
     */
    public function backup(Request $request)
    {
        try {
            $backupFilename = 'chatbot_backup_' . date('Y-m-d_H-i-s') . '.json';
            $backupPath = storage_path('app/backups/' . $backupFilename);
            
            if (!file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }
            
            $knowledge = ChatbotKnowledge::all();
            
            $backupData = [
                'backup_date' => now()->format('Y-m-d H:i:s'),
                'total_records' => $knowledge->count(),
                'knowledge' => $knowledge->map(function ($item) {
                    return [
                        'question' => $item->question,
                        'answer' => $item->answer,
                        'source' => $item->source,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            ];
            
            file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Backup berhasil dibuat',
                'data' => [
                    'filename' => $backupFilename,
                    'total_records' => $knowledge->count(),
                    'file_size' => $this->formatBytes(filesize($backupPath))
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Backup error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 10. RESTORE KNOWLEDGE BASE
     * =========================
     */
    public function restore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'backup_file' => 'required|file|mimes:json|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $file = $request->file('backup_file');
            $content = file_get_contents($file->getRealPath());
            $backupData = json_decode($content, true);
            
            if (!$backupData || !isset($backupData['knowledge'])) {
                throw new \Exception("Format backup file tidak valid");
            }
            
            if ($request->input('clear_existing', false)) {
                ChatbotKnowledge::truncate();
            }
            
            $restoredCount = 0;
            foreach ($backupData['knowledge'] as $knowledgeData) {
                ChatbotKnowledge::create([
                    'question' => $knowledgeData['question'],
                    'answer' => $knowledgeData['answer'],
                    'source' => $knowledgeData['source'],
                    'created_at' => $knowledgeData['created_at'] ?? now()
                ]);
                $restoredCount++;
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Restore berhasil',
                'data' => [
                    'total_restored' => $restoredCount,
                    'backup_date' => $backupData['backup_date'] ?? 'Unknown'
                ]
            ]);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Restore error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal restore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 11. CLEAR KNOWLEDGE BASE
     * =========================
     */
    public function clearAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|in:YES,DELETE_ALL'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Konfirmasi diperlukan. Kirim confirmation=DELETE_ALL untuk melanjutkan'
            ], 422);
        }

        try {
            $totalDeleted = ChatbotKnowledge::count();
            ChatbotKnowledge::truncate();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge base berhasil dikosongkan',
                'data' => [
                    'total_deleted' => $totalDeleted
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Clear all error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengosongkan knowledge base'
            ], 500);
        }
    }

    /**
     * =========================
     * 12. IMPORT SAMPLE DATA
     * =========================
     */
    public function importSample()
    {
        try {
            $sampleData = [
                [
                    'question' => 'Apa itu Gym GenZ?',
                    'answer' => 'Gym GenZ adalah pusat kebugaran modern yang dirancang khusus untuk generasi muda dengan fasilitas lengkap dan program fitness terkini.',
                    'source' => 'sample'
                ],
                [
                    'question' => 'Jam operasional gym?',
                    'answer' => 'Gym GenZ buka setiap hari dari jam 06:00 pagi hingga 22:00 malam.',
                    'source' => 'sample'
                ],
                [
                    'question' => 'Berapa biaya membership?',
                    'answer' => 'Kami menawarkan berbagai paket membership mulai dari Rp 300.000 per bulan hingga Rp 2.500.000 per tahun.',
                    'source' => 'sample'
                ],
                [
                    'question' => 'Apakah ada personal trainer?',
                    'answer' => 'Ya, kami memiliki certified personal trainer yang siap membantu mencapai target fitness Anda.',
                    'source' => 'sample'
                ],
                [
                    'question' => 'Apa saja fasilitas yang tersedia?',
                    'answer' => 'Kami memiliki area cardio, weight training, group classes, locker room, shower, dan café sehat.',
                    'source' => 'sample'
                ]
            ];
            
            $importedCount = 0;
            foreach ($sampleData as $data) {
                ChatbotKnowledge::create($data);
                $importedCount++;
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sample data berhasil diimport',
                'data' => [
                    'total_imported' => $importedCount
                ]
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Import sample error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengimport sample data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function untuk format bytes
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}