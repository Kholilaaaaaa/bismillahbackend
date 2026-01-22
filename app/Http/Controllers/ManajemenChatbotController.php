<?php
// D:\coba\gym-genz-api\app\Http\Controllers\ManajemenChatbotController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ChatbotKnowledge;

class ManajemenChatbotController extends Controller
{
    /**
     * =========================
     * 1. DASHBOARD STATISTIK
     * =========================
     */
    public function index()
    {
        try {
            Log::info('=== MANAJEMEN CHATBOT DASHBOARD ===');
            
            // Debug: Cek jumlah data di database
            $allData = ChatbotKnowledge::all();
            Log::info('Total records in database: ' . $allData->count());
            Log::info('Sample data:', $allData->take(3)->toArray());
            
            $totalQuestions = ChatbotKnowledge::count();
            Log::info('Total questions count: ' . $totalQuestions);
            
            $totalSources = ChatbotKnowledge::distinct('source')->count('source');
            Log::info('Total sources count: ' . $totalSources);

            $sourceStats = ChatbotKnowledge::select('source', DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->orderByDesc('total')
                ->get();
            
            Log::info('Source stats:', $sourceStats->toArray());

            $terbaru = ChatbotKnowledge::latest('created_at')
                ->limit(5)
                ->get(['id', 'question', 'source', 'created_at']);
            
            Log::info('Latest data:', $terbaru->toArray());

            return response()->json([
                'status' => 'success',
                'total_questions' => $totalQuestions,
                'total_sources' => $totalSources,
                'statistik_source' => $sourceStats,
                'terbaru_ditambahkan' => $terbaru->map(fn ($i) => [
                    'id' => $i->id,
                    'question_preview' => mb_substr($i->question, 0, 100) . (strlen($i->question) > 100 ? '...' : ''),
                    'source' => $i->source,
                    'ditambahkan' => $i->created_at->format('d M Y H:i'),
                ])
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Throwable $e) {
            Log::error('Dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 2. LIST KNOWLEDGE (FIXED)
     * =========================
     */
public function listKnowledge(Request $request)
{
    try {
        $perPage = (int) $request->input('per_page', 10);
        $search  = $request->input('search');
        $source  = $request->input('source');

        $query = ChatbotKnowledge::where('is_active', true);

        if ($source && $source !== 'all') {
            $query->where('source', $source);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data // ⬅️ PENTING: jangan dipecah
        ]);
    } catch (\Throwable $e) {
        Log::error('List knowledge error', ['e' => $e]);

        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memuat data'
        ], 500);
    }
}


    /**
     * =========================
     * 3. DETAIL
     * =========================
     */
    public function show($id)
    {
        try {
            Log::info('Showing knowledge ID:', ['id' => $id]);
            
            $knowledge = ChatbotKnowledge::find($id);

            if (!$knowledge) {
                Log::warning('Knowledge not found:', ['id' => $id]);
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            Log::info('Knowledge found:', $knowledge->toArray());
            return response()->json([
                'status' => 'success', 
                'data' => $knowledge
            ]);

        } catch (\Throwable $e) {
            Log::error('Show knowledge error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal mengambil detail'
            ], 500);
        }
    }

    /**
     * =========================
     * 4. IMPORT CSV (FIXED)
     * =========================
     */
    public function storeCSV(Request $request)
    {
        Log::info('=== CSV IMPORT REQUEST ===');
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            Log::error('CSV validation failed:', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            Log::info('CSV file details:', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $path
            ]);

            // Baca file CSV
            $rows = array_map('str_getcsv', file($path));
            
            Log::info('CSV rows count:', ['count' => count($rows)]);

            if (count($rows) < 2) {
                throw new \Exception('CSV file kosong atau hanya berisi header');
            }

            // Ambil header
            $header = array_map('strtolower', array_shift($rows));
            Log::info('CSV header:', $header);

            // Validasi header
            $requiredHeaders = ['question', 'answer'];
            foreach ($requiredHeaders as $required) {
                if (!in_array($required, $header)) {
                    throw new \Exception("Header CSV harus mengandung: " . implode(', ', $requiredHeaders));
                }
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    // Pastikan row memiliki cukup kolom
                    if (count($row) < 2) {
                        $errorCount++;
                        $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                        continue;
                    }

                    // Map data berdasarkan header
                    $rowData = array_combine($header, array_pad($row, count($header), ''));
                    
                    $question = trim($rowData['question'] ?? '');
                    $answer = trim($rowData['answer'] ?? '');

                    // Validasi data
                    if (empty($question) || strlen($question) < 3) {
                        $errorCount++;
                        $errors[] = "Baris " . ($index + 2) . ": Pertanyaan terlalu pendek";
                        continue;
                    }

                    if (empty($answer) || strlen($answer) < 10) {
                        $errorCount++;
                        $errors[] = "Baris " . ($index + 2) . ": Jawaban terlalu pendek";
                        continue;
                    }

                    // Simpan ke database
                    ChatbotKnowledge::create([
                        'question' => $question,
                        'answer' => $answer,
                        'source' => 'csv_import',
                        'category' => $rowData['category'] ?? null,
                        'tags' => $rowData['tags'] ?? null,
                        'is_active' => true
                    ]);

                    $successCount++;
                    Log::info("Imported row {$index}: {$question}");

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                    Log::error("Error importing row {$index}: " . $e->getMessage());
                }
            }

            DB::commit();

            $message = "Import selesai. Berhasil: {$successCount}, Gagal: {$errorCount}";
            Log::info($message);

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'rows' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CSV import error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal mengimport CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 5. TAMBAH MANUAL (FIXED)
     * =========================
     */
    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|min:3',
            'answer'   => 'required|string|min:5',
            'source'   => 'nullable|string',
        ]);
        
        ChatbotKnowledge::create([
            'question' => trim($row[0]),
            'answer' => trim($row[1]),
            'source' => 'csv_import',
            'model_type' => 'csv',
            'is_active' => true,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Knowledge berhasil ditambahkan',
            'data' => $knowledge
        ]);
    }


    /**
     * =========================
     * 6. UPDATE
     * =========================
     */
    public function update(Request $request, $id)
    {
        Log::info('=== UPDATE KNOWLEDGE REQUEST ===', ['id' => $id]);

        $knowledge = ChatbotKnowledge::find($id);
        if (!$knowledge) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|string|min:3|max:500',
            'answer' => 'sometimes|string|min:10|max:5000',
            'source' => 'sometimes|string|max:100',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|string|max:200',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            Log::info('Updating knowledge with data:', $data);
            
            $knowledge->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge berhasil diupdate',
                'data' => $knowledge
            ]);

        } catch (\Throwable $e) {
            Log::error('Update error:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 7. DELETE
     * =========================
     */
    public function destroy($id)
    {
        try {
            Log::info('Deleting knowledge ID:', ['id' => $id]);
            
            $knowledge = ChatbotKnowledge::find($id);
            if (!$knowledge) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $knowledge->delete();

            return response()->json([
                'status' => 'success', 
                'message' => 'Data berhasil dihapus'
            ]);

        } catch (\Throwable $e) {
            Log::error('Delete error:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================
     * 8. SEARCH
     * =========================
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Query harus minimal 2 karakter'
            ], 422);
        }

        try {
            $query = $validator->validated()['query'];

            $results = ChatbotKnowledge::where(function ($q) use ($query) {
                $q->where('question', 'like', "%{$query}%")
                  ->orWhere('answer', 'like', "%{$query}%")
                  ->orWhere('source', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('tags', 'like', "%{$query}%");
            })->limit(10)->get();

            return response()->json([
                'status' => 'success',
                'results' => $results
            ]);

        } catch (\Throwable $e) {
            Log::error('Search error:', [
                'query' => $request->input('query'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencari'
            ], 500);
        }
    }

    /**
     * =========================
     * 9. GET ALL DATA (TAMBAHAN)
     * =========================
     */
    public function getAll()
    {
        try {
            $data = ChatbotKnowledge::all();
            return response()->json([
                'status' => 'success',
                'data' => $data,
                'total' => $data->count()
            ]);
        } catch (\Throwable $e) {
            Log::error('Get all error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil semua data'
            ], 500);
        }
    }

    /**
     * =========================
     * 10. TOGGLE ACTIVE STATUS
     * =========================
     */
    public function toggleActive($id)
    {
        try {
            $knowledge = ChatbotKnowledge::find($id);
            if (!$knowledge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $knowledge->is_active = !$knowledge->is_active;
            $knowledge->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Status berhasil diubah',
                'data' => $knowledge
            ]);
        } catch (\Throwable $e) {
            Log::error('Toggle active error:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah status'
            ], 500);
        }
    }

    // Fungsi lainnya (backup, restore, clearAll, importSample) tetap sama seperti sebelumnya
    // ... (kode untuk backup, restore, clearAll, importSample tetap sama)
}