<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Feedback;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Log;

class FeedBackController extends Controller
{
    /**
     * Store a newly created feedback.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'required|string|min:10|max:500',
            ], [
                'rating.required' => 'Rating wajib diisi',
                'rating.integer' => 'Rating harus berupa angka',
                'rating.min' => 'Rating minimal 1',
                'rating.max' => 'Rating maksimal 5',
                'review.required' => 'Review wajib diisi',
                'review.string' => 'Review harus berupa teks',
                'review.min' => 'Review minimal 10 karakter',
                'review.max' => 'Review maksimal 500 karakter',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ambil user dari request->pengguna (sesuai middleware)
            $pengguna = $request->pengguna;
            
            if (!$pengguna) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak ditemukan',
                    'debug' => 'Middleware mungkin belum dijalankan atau token invalid'
                ], 401);
            }

            // Cek apakah user sudah memberikan feedback sebelumnya
            $existingFeedback = Feedback::where('id_pengguna', $pengguna->id)->first();
            
            if ($existingFeedback) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah memberikan feedback sebelumnya',
                    'data' => [
                        'existing_feedback' => [
                            'id' => $existingFeedback->id,
                            'rating' => $existingFeedback->rating,
                            'review' => $existingFeedback->review,
                            'created_at' => $existingFeedback->created_at->format('Y-m-d H:i:s')
                        ]
                    ]
                ], 409); // 409 Conflict
            }

            // Create feedback
            $feedback = Feedback::create([
                'id_pengguna' => $pengguna->id,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            // Load user data with feedback
            $feedback->load('pengguna:id,nama_lengkap,email');

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil disimpan',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'rating' => $feedback->rating,
                        'review' => $feedback->review,
                        'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $feedback->updated_at->format('Y-m-d H:i:s'),
                        'pengguna' => [
                            'id' => $feedback->pengguna->id,
                            'nama_lengkap' => $feedback->pengguna->nama_lengkap,
                            'email' => $feedback->pengguna->email,
                        ]
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Feedback Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => $pengguna->id ?? 'null'
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update existing feedback
     */
    public function update(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'required|string|min:10|max:500',
            ], [
                'rating.required' => 'Rating wajib diisi',
                'rating.integer' => 'Rating harus berupa angka',
                'rating.min' => 'Rating minimal 1',
                'rating.max' => 'Rating maksimal 5',
                'review.required' => 'Review wajib diisi',
                'review.string' => 'Review harus berupa teks',
                'review.min' => 'Review minimal 10 karakter',
                'review.max' => 'Review maksimal 500 karakter',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ambil user dari request->pengguna
            $pengguna = $request->pengguna;
            
            if (!$pengguna) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak ditemukan'
                ], 401);
            }

            // Cari feedback yang akan diupdate
            $feedback = Feedback::where('id_pengguna', $pengguna->id)->first();

            if (!$feedback) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Feedback tidak ditemukan',
                    'hint' => 'Silakan buat feedback terlebih dahulu'
                ], 404);
            }

            // Simpan data lama untuk log jika diperlukan
            $oldData = [
                'rating' => $feedback->rating,
                'review' => $feedback->review,
            ];

            // Update feedback
            $feedback->update([
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            // Reload data dengan relasi pengguna
            $feedback->load('pengguna:id,nama_lengkap,email');

            // Log perubahan (opsional, untuk debugging)
            Log::info('Feedback updated', [
                'user_id' => $pengguna->id,
                'feedback_id' => $feedback->id,
                'old_data' => $oldData,
                'new_data' => [
                    'rating' => $feedback->rating,
                    'review' => $feedback->review,
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil diperbarui',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'rating' => $feedback->rating,
                        'review' => $feedback->review,
                        'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $feedback->updated_at->format('Y-m-d H:i:s'),
                        'pengguna' => [
                            'id' => $feedback->pengguna->id,
                            'nama_lengkap' => $feedback->pengguna->nama_lengkap,
                            'email' => $feedback->pengguna->email,
                        ]
                    ],
                    'changes' => [
                        'rating' => $oldData['rating'] != $feedback->rating,
                        'review' => $oldData['review'] != $feedback->review,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Feedback Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => $pengguna->id ?? 'null'
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete user's feedback
     */
    public function destroy(Request $request)
    {
        try {
            // Ambil user dari request->pengguna
            $pengguna = $request->pengguna;
            
            if (!$pengguna) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak ditemukan'
                ], 401);
            }

            // Cari feedback yang akan dihapus
            $feedback = Feedback::where('id_pengguna', $pengguna->id)->first();

            if (!$feedback) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Feedback tidak ditemukan'
                ], 404);
            }

            // Simpan data sebelum dihapus untuk log
            $deletedFeedback = [
                'id' => $feedback->id,
                'rating' => $feedback->rating,
                'review' => $feedback->review,
            ];

            // Hapus feedback
            $feedback->delete();

            // Log penghapusan
            Log::info('Feedback deleted', [
                'user_id' => $pengguna->id,
                'deleted_feedback' => $deletedFeedback
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil dihapus',
                'data' => [
                    'deleted_feedback' => $deletedFeedback
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Feedback Delete Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => $pengguna->id ?? 'null'
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user's feedback (if exists)
     */
    public function myFeedback(Request $request)
    {
        try {
            // Ambil user dari request->pengguna
            $pengguna = $request->pengguna;
            
            if (!$pengguna) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak ditemukan'
                ], 401);
            }
            
            $feedback = Feedback::where('id_pengguna', $pengguna->id)
                ->with('pengguna:id,nama_lengkap,email')
                ->first();

            if (!$feedback) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Anda belum memberikan feedback',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback ditemukan',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'rating' => $feedback->rating,
                        'review' => $feedback->review,
                        'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $feedback->updated_at->format('Y-m-d H:i:s'),
                        'pengguna' => [
                            'id' => $feedback->pengguna->id,
                            'nama_lengkap' => $feedback->pengguna->nama_lengkap,
                            'email' => $feedback->pengguna->email,
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('My Feedback Error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}