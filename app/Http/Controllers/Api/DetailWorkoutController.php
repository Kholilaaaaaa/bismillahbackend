<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailWorkout;
use App\Models\Workout;
use App\Services\MlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DetailWorkoutController extends Controller
{
    protected $mlService;
    
    // Session storage directory
    protected $sessionStoragePath;
    
    // Cache untuk real-time data
    protected $frameCache = [];
    protected $sessionCache = [];

    public function __construct(MlService $mlService)
    {
        $this->mlService = $mlService;
        $this->sessionStoragePath = storage_path('sessions');
        
        // Create directory if not exists
        if (!file_exists($this->sessionStoragePath)) {
            mkdir($this->sessionStoragePath, 0777, true);
        }
        
        // Tambahkan middleware auth.token untuk semua method kecuali test
        $this->middleware('auth.token')->except(['testConnection']);
    }

    /**
     * ============================================
     * REAL-TIME CAMERA PROCESSING ENDPOINTS
     * ============================================
     */
    
    /**
     * Process single camera frame for real-time feedback
     * Endpoint: POST /api/detailworkout/process-frame
     */
    public function processCameraFrame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'frame_data' => 'required|array',
            'expected_exercise' => 'required|string|in:pushup,shoulder_press,t_bar_row',
            'workout_id' => 'nullable|exists:workouts,id',
            'session_id' => 'nullable|string',
            'frame_timestamp' => 'nullable|numeric',
            'frame_index' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $rawFrameData = $request->frame_data;
            $expectedExercise = $request->expected_exercise;
            $workoutId = $request->workout_id;
            $sessionId = $request->session_id;
            $frameTimestamp = $request->frame_timestamp ?? microtime(true);
            $frameIndex = $request->frame_index ?? 0;

            // Standardize frame data format untuk ML Service
            $standardizedData = $this->standardizeFrameData($rawFrameData);
            
            Log::debug('Processing camera frame', [
                'user_id' => $user->id,
                'expected_exercise' => $expectedExercise,
                'original_data_type' => $this->getDataType($rawFrameData),
                'standardized_type' => $this->getDataType($standardizedData),
                'has_pose_landmarks' => isset($standardizedData['pose_landmarks']),
                'landmarks_count' => isset($standardizedData['pose_landmarks']) ? count($standardizedData['pose_landmarks']) : 0,
                'frame_timestamp' => $frameTimestamp,
                'workout_id' => $workoutId,
                'session_id' => $sessionId
            ]);

            // Process frame dengan ML Service
            $startTime = microtime(true);
            $result = $this->mlService->processRealTimeFrame($standardizedData, $expectedExercise);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2); // ms

            // Validasi result dari ML Service
            if (!isset($result['success'])) {
                $result['success'] = false;
                $result['error'] = 'Invalid response from ML service';
            }

            // Jika ada session_id, update session data
            if ($sessionId) {
                $this->updateSessionFrame($sessionId, $frameIndex, $result);
            }

            // Generate user feedback
            $userFeedback = $this->generateRealTimeFeedback($result);

            // Format response untuk real-time feedback
            $response = [
                'success' => $result['success'] ?? false,
                'frame_analysis' => $result['frame_analysis'] ?? null,
                'feedback' => $result['feedback'] ?? null,
                'user_feedback' => $userFeedback,
                'frame_info' => [
                    'timestamp' => $frameTimestamp,
                    'index' => $frameIndex,
                    'processing_time_ms' => $processingTime,
                    'total_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
                    'data_type' => $this->getDataType($rawFrameData),
                    'standardized' => $this->getDataType($standardizedData)
                ],
                'exercise_detected' => $result['frame_analysis']['prediction']['exercise_detected'] ?? $expectedExercise,
                'is_correct_exercise' => $result['frame_analysis']['prediction']['is_correct_exercise'] ?? true,
                'confidence_score' => $result['frame_analysis']['confidence_score'] ?? 0.7,
                'form_issues' => $result['frame_analysis']['form_check']['issues'] ?? [],
                'rep_info' => $result['frame_analysis']['rep_detection'] ?? null,
                'session_updated' => $sessionId ? true : false
            ];

            // Jika ada rep completed, tambahkan info
            if (isset($result['frame_analysis']['rep_detection']['rep_completed']) && 
                $result['frame_analysis']['rep_detection']['rep_completed']) {
                $response['rep_completed'] = true;
                $response['total_reps'] = $result['frame_analysis']['rep_detection']['total_reps'] ?? 0;
                
                // Jika ada workout_id, bisa langsung simpan rep
                if ($workoutId && $sessionId) {
                    $this->handleRepCompletion($user->id, $workoutId, $sessionId, $expectedExercise, $response);
                }
            }

            // Jika ada error dari ML Service
            if (isset($result['error'])) {
                $response['ml_error'] = $result['error'];
                $response['fallback_prediction'] = $result['fallback_prediction'] ?? null;
            }

            Log::debug('Frame processed', [
                'processing_time_ms' => $processingTime,
                'feedback' => $userFeedback,
                'confidence' => $response['confidence_score'],
                'success' => $result['success'] ?? false
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Camera frame processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? 'unknown',
                'expected_exercise' => $request->expected_exercise ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Frame processing failed: ' . $e->getMessage(),
                'fallback_feedback' => 'Processing frame...',
                'frame_info' => [
                    'timestamp' => $request->frame_timestamp ?? microtime(true),
                    'index' => $request->frame_index ?? 0,
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Process batch of frames for exercise analysis
     * Endpoint: POST /api/detailworkout/process-batch-frames
     */
    public function processBatchFrames(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'frames' => 'required|array|min:1',
            'frames.*.frame_data' => 'required|array',
            'expected_exercise' => 'required|string|in:pushup,shoulder_press,t_bar_row',
            'workout_id' => 'required|exists:workouts,id',
            'session_id' => 'nullable|string',
            'batch_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $frames = $request->frames;
            $expectedExercise = $request->expected_exercise;
            $workoutId = $request->workout_id;
            $sessionId = $request->session_id;
            $batchId = $request->batch_id ?? uniqid('batch_');
            
            $workout = Workout::find($workoutId);
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found'
                ], 404);
            }

            Log::info('Processing batch frames', [
                'user_id' => $user->id,
                'workout_id' => $workoutId,
                'session_id' => $sessionId,
                'batch_id' => $batchId,
                'frame_count' => count($frames),
                'expected_exercise' => $expectedExercise
            ]);

            // Standardize semua frame data
            $standardizedFrames = [];
            foreach ($frames as $frame) {
                $standardizedFrames[] = $this->standardizeFrameData($frame['frame_data']);
            }

            // Process batch frames dengan ML Service
            $batchResult = $this->mlService->batchProcessFrames($standardizedFrames, $expectedExercise);
            
            if (!isset($batchResult['success'])) {
                $batchResult['success'] = false;
                $batchResult['error'] = 'Invalid response from ML service';
            }

            if (!$batchResult['success']) {
                throw new \Exception('Batch processing failed: ' . ($batchResult['error'] ?? 'Unknown error'));
            }

            // Hitung statistik dari batch analysis
            $batchAnalysis = $batchResult['batch_analysis'] ?? [
                'total_frames' => count($frames),
                'successful_frames' => 0,
                'success_rate' => 0,
                'correct_exercise_rate' => 0,
                'correct_form_rate' => 0,
                'total_reps_detected' => 0,
                'average_confidence' => 0
            ];
            
            // Simpan sebagai detail workout jika hasil memuaskan
            $detailSaved = false;
            $detailWorkout = null;
            
            if ($batchAnalysis['success_rate'] > 30 && $batchAnalysis['correct_exercise_rate'] > 40) {
                
                // Get next urutan
                $lastUrutan = DetailWorkout::where('id_workout', $workoutId)
                    ->max('urutan');
                $nextUrutan = $lastUrutan ? $lastUrutan + 1 : 1;
                
                // Estimated reps dari batch analysis
                $estimatedReps = $batchAnalysis['total_reps_detected'] > 0 
                    ? $batchAnalysis['total_reps_detected'] 
                    : min(20, max(1, intval(count($frames) / 15)));
                
                // Duration based on frame count (asumsi 30 FPS)
                $durationSeconds = max(1, intval(count($frames) / 30));
                
                // Create detail workout
                $detailWorkout = DetailWorkout::create([
                    'id_workout' => $workoutId,
                    'label_ml' => $expectedExercise,
                    'repetisi' => $estimatedReps,
                    'set' => 1,
                    'durasi_detik' => $durationSeconds,
                    'catatan' => "Batch Processing Report:\n" .
                                "Batch ID: {$batchId}\n" .
                                "Frame Count: " . count($frames) . "\n" .
                                "Success Rate: " . round($batchAnalysis['success_rate'], 1) . "%\n" .
                                "Correct Exercise Rate: " . round($batchAnalysis['correct_exercise_rate'], 1) . "%\n" .
                                "Correct Form Rate: " . round($batchAnalysis['correct_form_rate'], 1) . "%\n" .
                                "Reps Detected: " . $batchAnalysis['total_reps_detected'] . "\n" .
                                "Avg Confidence: " . round($batchAnalysis['average_confidence'], 3) . "\n" .
                                "Top Form Issues: " . ($this->formatFormIssues($batchAnalysis['form_issues_summary'] ?? [])),
                    'urutan' => $nextUrutan
                ]);
                
                // Update workout statistics
                $this->updateWorkoutStatistics($workoutId);
                
                $detailSaved = true;
                
                Log::info('Batch saved as detail workout', [
                    'detail_id' => $detailWorkout->id_detail_workout,
                    'reps' => $estimatedReps,
                    'duration' => $durationSeconds
                ]);
            } else {
                Log::warning('Batch processing quality too low, not saving to database', [
                    'success_rate' => $batchAnalysis['success_rate'] ?? 0,
                    'correct_exercise_rate' => $batchAnalysis['correct_exercise_rate'] ?? 0
                ]);
            }

            // Update session jika ada
            if ($sessionId) {
                $this->updateSessionBatch($sessionId, $batchId, $batchResult, $detailSaved);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch frames processed successfully',
                'data' => [
                    'batch_analysis' => $batchAnalysis,
                    'detail_saved' => $detailSaved,
                    'detail_workout' => $detailSaved ? [
                        'id_detail_workout' => $detailWorkout->id_detail_workout,
                        'id_workout' => $detailWorkout->id_workout,
                        'label_ml' => $detailWorkout->label_ml,
                        'repetisi' => $detailWorkout->repetisi,
                        'durasi_detik' => $detailWorkout->durasi_detik,
                        'urutan' => $detailWorkout->urutan,
                        'created_at' => $detailWorkout->created_at->format('Y-m-d H:i:s')
                    ] : null,
                    'batch_info' => [
                        'batch_id' => $batchId,
                        'frame_count' => count($frames),
                        'processing_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
                        'ml_service_success' => $batchResult['success'] ?? false
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch frames processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown',
                'workout_id' => $request->workout_id ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch frames: ' . $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Start real-time workout session
     * Endpoint: POST /api/detailworkout/start-session
     */
    public function startWorkoutSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workout_id' => 'required|exists:workouts,id',
            'session_id' => 'nullable|string',
            'expected_exercises' => 'nullable|array',
            'expected_exercises.*' => 'string|in:pushup,shoulder_press,t_bar_row',
            'session_type' => 'nullable|string|in:realtime,batch,hybrid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $workoutId = $request->workout_id;
            $sessionId = $request->session_id ?? 'session_' . uniqid();
            $expectedExercises = $request->expected_exercises ?? ['pushup'];
            $sessionType = $request->session_type ?? 'realtime';
            
            $workout = Workout::find($workoutId);
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found'
                ], 404);
            }

            // Update workout status
            $workout->update([
                'status' => 'sedang dilakukan',
                'updated_at' => now()
            ]);

            // Initialize session data
            $sessionData = [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'workout_id' => $workoutId,
                'workout_name' => $workout->nama_workout,
                'start_time' => now()->format('Y-m-d H:i:s'),
                'start_timestamp' => microtime(true),
                'expected_exercises' => $expectedExercises,
                'session_type' => $sessionType,
                'frame_count' => 0,
                'batches_processed' => 0,
                'total_frames' => 0,
                'current_exercise_index' => 0,
                'current_exercise' => $expectedExercises[0] ?? 'pushup',
                'reps_per_exercise' => array_fill_keys($expectedExercises, 0),
                'sets_per_exercise' => array_fill_keys($expectedExercises, 0),
                'frame_analyses' => [],
                'batch_results' => [],
                'ml_health' => $this->mlService->checkHealth(),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];

            // Save session data
            $this->saveSessionData($sessionId, $sessionData);

            Log::info('Workout session started', [
                'user_id' => $user->id,
                'workout_id' => $workoutId,
                'session_id' => $sessionId,
                'expected_exercises' => $expectedExercises,
                'session_type' => $sessionType,
                'ml_health' => $sessionData['ml_health']['overall_healthy'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout session started',
                'data' => [
                    'session' => [
                        'session_id' => $sessionId,
                        'workout_id' => $workoutId,
                        'workout_name' => $workout->nama_workout,
                        'start_time' => $sessionData['start_time'],
                        'expected_exercises' => $expectedExercises,
                        'current_exercise' => $sessionData['current_exercise'],
                        'session_type' => $sessionType,
                        'ml_service_ready' => $sessionData['ml_health']['overall_healthy'] ?? false
                    ],
                    'workout' => [
                        'id' => $workout->id,
                        'nama_workout' => $workout->nama_workout,
                        'status' => $workout->status,
                        'exercises' => $workout->exercises
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Start workout session error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start workout session',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get session status
     * Endpoint: GET /api/detailworkout/session-status/{session_id}
     */
    public function getSessionStatus(Request $request, $sessionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessionData = $this->getSessionData($sessionId);

            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Verifikasi session milik user
            if ($sessionData['user_id'] != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session access denied'
                ], 403);
            }

            // Calculate session statistics
            $elapsedSeconds = microtime(true) - $sessionData['start_timestamp'];
            $framesPerSecond = $sessionData['frame_count'] > 0 ? 
                round($sessionData['frame_count'] / $elapsedSeconds, 1) : 0;
            
            $currentTime = now()->format('Y-m-d H:i:s');

            return response()->json([
                'success' => true,
                'data' => [
                    'session' => $sessionData,
                    'statistics' => [
                        'elapsed_seconds' => round($elapsedSeconds, 1),
                        'frame_count' => $sessionData['frame_count'],
                        'frames_per_second' => $framesPerSecond,
                        'batches_processed' => $sessionData['batches_processed'],
                        'total_reps' => array_sum($sessionData['reps_per_exercise']),
                        'current_exercise' => $sessionData['current_exercise'],
                        'current_time' => $currentTime,
                        'ml_service_healthy' => $sessionData['ml_health']['overall_healthy'] ?? false
                    ],
                    'status' => 'active'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get session status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get session status',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * End workout session
     * Endpoint: POST /api/detailworkout/end-session
     */
    public function endWorkoutSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'final_notes' => 'nullable|string',
            'auto_save_details' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessionId = $request->session_id;
            $finalNotes = $request->final_notes;
            $autoSave = $request->input('auto_save_details', true);
            
            $sessionData = $this->getSessionData($sessionId);
            
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Verifikasi session milik user
            if ($sessionData['user_id'] != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session access denied'
                ], 403);
            }

            $workoutId = $sessionData['workout_id'];
            $workout = Workout::find($workoutId);
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found'
                ], 404);
            }

            // Calculate final statistics
            $totalReps = array_sum($sessionData['reps_per_exercise']);
            $totalSets = array_sum($sessionData['sets_per_exercise']);
            $elapsedSeconds = microtime(true) - $sessionData['start_timestamp'];
            
            // Update session data
            $sessionData['end_time'] = now()->format('Y-m-d H:i:s');
            $sessionData['end_timestamp'] = microtime(true);
            $sessionData['total_duration_seconds'] = round($elapsedSeconds, 1);
            $sessionData['total_reps'] = $totalReps;
            $sessionData['total_sets'] = $totalSets;
            $sessionData['final_notes'] = $finalNotes;
            $sessionData['completed'] = true;
            $sessionData['updated_at'] = now()->format('Y-m-d H:i:s');

            // Auto-save details jika diinginkan
            $savedDetails = [];
            if ($autoSave && $totalReps > 0) {
                foreach ($sessionData['reps_per_exercise'] as $exercise => $reps) {
                    if ($reps > 0) {
                        $lastUrutan = DetailWorkout::where('id_workout', $workoutId)
                            ->max('urutan');
                        $nextUrutan = $lastUrutan ? $lastUrutan + 1 : 1;
                        
                        $detailWorkout = DetailWorkout::create([
                            'id_workout' => $workoutId,
                            'label_ml' => $exercise,
                            'repetisi' => $reps,
                            'set' => $sessionData['sets_per_exercise'][$exercise] ?? 1,
                            'durasi_detik' => round($elapsedSeconds / count($sessionData['reps_per_exercise'])),
                            'catatan' => "Session ID: {$sessionId}\n" .
                                        "Auto-saved from real-time session\n" .
                                        "Total Frames: {$sessionData['frame_count']}\n" .
                                        "Batches Processed: {$sessionData['batches_processed']}\n" .
                                        "ML Service Health: " . ($sessionData['ml_health']['overall_healthy'] ? 'Good' : 'Issues') . "\n" .
                                        "Final Notes: " . ($finalNotes ?? 'N/A'),
                            'urutan' => $nextUrutan
                        ]);
                        
                        $savedDetails[] = [
                            'exercise' => $exercise,
                            'reps' => $reps,
                            'sets' => $sessionData['sets_per_exercise'][$exercise] ?? 1,
                            'detail_id' => $detailWorkout->id_detail_workout
                        ];
                    }
                }
                
                // Update workout statistics
                $this->updateWorkoutStatistics($workoutId);
            }

            // Update workout
            $workout->update([
                'status' => 'selesai',
                'updated_at' => now()
            ]);

            // Save final session data
            $this->saveSessionData($sessionId, $sessionData);

            // Clean up session file after 1 hour
            $this->scheduleSessionCleanup($sessionId);

            DB::commit();

            Log::info('Workout session ended', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'workout_id' => $workoutId,
                'total_reps' => $totalReps,
                'total_duration' => round($elapsedSeconds, 1),
                'details_saved' => count($savedDetails)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout session ended successfully',
                'data' => [
                    'session_summary' => [
                        'session_id' => $sessionId,
                        'workout_id' => $workoutId,
                        'start_time' => $sessionData['start_time'],
                        'end_time' => $sessionData['end_time'],
                        'total_duration_seconds' => $sessionData['total_duration_seconds'],
                        'frame_count' => $sessionData['frame_count'],
                        'batches_processed' => $sessionData['batches_processed'],
                        'total_reps' => $totalReps,
                        'total_sets' => $totalSets,
                        'reps_by_exercise' => $sessionData['reps_per_exercise']
                    ],
                    'workout' => [
                        'id' => $workout->id,
                        'nama_workout' => $workout->nama_workout,
                        'status' => $workout->status,
                        'exercises' => $workout->exercises
                    ],
                    'saved_details' => $savedDetails,
                    'auto_saved' => $autoSave,
                    'ml_service_status' => $sessionData['ml_health']['overall_healthy'] ?? false
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('End workout session error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to end workout session',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * ============================================
     * HELPER METHODS FOR REAL-TIME PROCESSING
     * ============================================
     */
    
    private function getDataType($data): string
    {
        if (empty($data)) return 'empty';
        
        // Cek jika ini adalah pose landmarks (format MediaPipe)
        if (isset($data['pose_landmarks'])) {
            return 'pose_landmarks';
        }
        
        // Cek jika ini array of landmarks
        if (is_array($data) && isset($data[0])) {
            // Cek format landmark: bisa array asosiatif atau array numerik
            $firstItem = $data[0];
            
            if (is_array($firstItem)) {
                if (isset($firstItem['x']) && isset($firstItem['y'])) {
                    return 'pose_landmarks_array';
                }
                if (isset($firstItem[0]) && isset($firstItem[1])) {
                    return 'pose_landmarks_numeric';
                }
            }
        }
        
        // Cek jika ini sequence data untuk ML
        if (is_array($data) && is_array($data[0] ?? null) && is_numeric($data[0][0] ?? null)) {
            return 'sequence_data';
        }
        
        return 'unknown';
    }

    /**
     * Convert various frame data formats to standardized format for ML Service
     */
    private function standardizeFrameData(array $frameData): array
    {
        // Jika frame_data sudah dalam format yang diharapkan
        if (isset($frameData['pose_landmarks'])) {
            return $frameData;
        }
        
        // Jika frame_data adalah array langsung dari landmarks
        if (is_array($frameData) && isset($frameData[0])) {
            $firstItem = $frameData[0];
            
            // Cek berbagai format landmark
            if (is_array($firstItem)) {
                if (isset($firstItem['x']) && isset($firstItem['y'])) {
                    // Format: [['x'=>0.5, 'y'=>0.5, 'z'=>0, 'visibility'=>0.9], ...]
                    return ['pose_landmarks' => $frameData];
                }
                if (isset($firstItem[0]) && isset($firstItem[1])) {
                    // Format: [[0.5, 0.5, 0, 0.9], ...]
                    $converted = [];
                    foreach ($frameData as $landmark) {
                        $converted[] = [
                            'x' => $landmark[0] ?? 0.5,
                            'y' => $landmark[1] ?? 0.5,
                            'z' => $landmark[2] ?? 0,
                            'visibility' => $landmark[3] ?? 0.8
                        ];
                    }
                    return ['pose_landmarks' => $converted];
                }
            }
        }
        
        // Fallback: create dummy pose landmarks
        Log::warning('Unknown frame data format, creating fallback pose data', [
            'data_keys' => array_keys($frameData)
        ]);
        
        return ['pose_landmarks' => $this->createFallbackPoseData()];
    }

    /**
     * Create fallback pose data when real pose detection fails
     */
    private function createFallbackPoseData(): array
    {
        $poseData = [];
        for ($i = 0; $i < 25; $i++) {
            $poseData[] = [
                'x' => 0.5 + (rand(-20, 20) / 100.0),
                'y' => 0.5 + (rand(-20, 20) / 100.0),
                'z' => 0,
                'visibility' => 0.8
            ];
        }
        return $poseData;
    }

    private function generateRealTimeFeedback(array $mlResult): string
    {
        if (!$mlResult['success'] || !isset($mlResult['feedback'])) {
            return 'Processing...';
        }
        
        // Handle jika ML service memberikan error
        if (isset($mlResult['error'])) {
            return 'Analisis pose...';
        }
        
        return $mlResult['feedback'] ?? 'Processing...';
    }

    private function updateSessionFrame($sessionId, $frameIndex, $analysis)
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) return;

        $sessionData['frame_count']++;
        $sessionData['frame_analyses'][$frameIndex] = [
            'index' => $frameIndex,
            'timestamp' => microtime(true),
            'analysis_summary' => [
                'exercise_detected' => $analysis['frame_analysis']['prediction']['exercise_detected'] ?? 'unknown',
                'confidence' => $analysis['frame_analysis']['confidence_score'] ?? 0,
                'form_correct' => $analysis['frame_analysis']['form_check']['is_correct'] ?? false,
                'rep_completed' => $analysis['frame_analysis']['rep_detection']['rep_completed'] ?? false
            ]
        ];
        
        // Update reps count jika ada rep completed
        if (isset($analysis['frame_analysis']['rep_detection']['rep_completed']) && 
            $analysis['frame_analysis']['rep_detection']['rep_completed']) {
            
            $exercise = $analysis['frame_analysis']['prediction']['exercise_detected'] ?? 
                       $sessionData['current_exercise'];
            
            if (isset($sessionData['reps_per_exercise'][$exercise])) {
                $sessionData['reps_per_exercise'][$exercise]++;
                
                // Reset set counter jika reps mencapai 10 (contoh)
                if ($sessionData['reps_per_exercise'][$exercise] % 10 == 0) {
                    $sessionData['sets_per_exercise'][$exercise] = 
                        ($sessionData['sets_per_exercise'][$exercise] ?? 0) + 1;
                }
            }
        }

        $sessionData['updated_at'] = now()->format('Y-m-d H:i:s');
        $this->saveSessionData($sessionId, $sessionData);
    }

    private function updateSessionBatch($sessionId, $batchId, $batchResult, $detailSaved)
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) return;

        $sessionData['batches_processed']++;
        $sessionData['total_frames'] += $batchResult['batch_analysis']['total_frames'] ?? 0;
        
        $sessionData['batch_results'][$batchId] = [
            'batch_id' => $batchId,
            'timestamp' => microtime(true),
            'frame_count' => $batchResult['batch_analysis']['total_frames'] ?? 0,
            'success_rate' => $batchResult['batch_analysis']['success_rate'] ?? 0,
            'reps_detected' => $batchResult['batch_analysis']['total_reps_detected'] ?? 0,
            'detail_saved' => $detailSaved
        ];

        $sessionData['updated_at'] = now()->format('Y-m-d H:i:s');
        $this->saveSessionData($sessionId, $sessionData);
    }

    private function handleRepCompletion($userId, $workoutId, $sessionId, $exercise, $frameResponse)
    {
        // Log rep completion untuk analytics
        Log::info('Rep completed', [
            'user_id' => $userId,
            'workout_id' => $workoutId,
            'session_id' => $sessionId,
            'exercise' => $exercise,
            'total_reps' => $frameResponse['total_reps'] ?? 0,
            'confidence' => $frameResponse['confidence_score'] ?? 0,
            'form_issues' => count($frameResponse['form_issues'] ?? [])
        ]);
    }

    private function saveSessionData($sessionId, $data)
    {
        $filename = $this->sessionStoragePath . '/' . $sessionId . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function getSessionData($sessionId)
    {
        $filename = $this->sessionStoragePath . '/' . $sessionId . '.json';
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            return $data ?: null;
        }
        return null;
    }

    private function scheduleSessionCleanup($sessionId)
    {
        // Schedule cleanup in 1 hour
        $cleanupTime = time() + 3600;
        $cleanupFile = $this->sessionStoragePath . '/cleanup.json';
        
        $cleanupList = [];
        if (file_exists($cleanupFile)) {
            $cleanupList = json_decode(file_get_contents($cleanupFile), true) ?: [];
        }
        
        $cleanupList[$sessionId] = $cleanupTime;
        file_put_contents($cleanupFile, json_encode($cleanupList, JSON_PRETTY_PRINT));
    }

    private function formatFormIssues(array $formIssues): string
    {
        if (empty($formIssues)) {
            return 'None detected';
        }
        
        $issues = [];
        foreach ($formIssues as $issue) {
            if (is_array($issue)) {
                $issues[] = $issue['issue'] . ' (' . $issue['count'] . 'x)';
            } else {
                $issues[] = (string) $issue;
            }
        }
        
        return implode(', ', array_slice($issues, 0, 3));
    }

    /**
     * ============================================
     * KEEP YOUR EXISTING METHODS BELOW
     * ============================================
     */
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // ✅ Gunakan custom auth Anda
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('Unauthorized access to detail workouts');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            Log::info('DetailWorkout API Accessed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'endpoint' => 'index',
                'ip' => $request->ip()
            ]);
            
            // Mulai query
            $query = DetailWorkout::with(['workout.jadwalWorkout']);
            
            // ✅ Filter by workout_id
            if ($request->has('workout_id')) {
                $query->where('id_workout', $request->workout_id);
                
                // Verifikasi workout milik user (jika ada relasi)
                $workout = Workout::with('jadwalWorkout')->find($request->workout_id);
                if ($workout && $workout->jadwalWorkout) {
                    Log::info('Filtering by workout', [
                        'workout_id' => $workout->id,
                        'workout_name' => $workout->nama_workout
                    ]);
                }
            }
            
            // Filter by date
            if ($request->has('date')) {
                $query->whereDate('created_at', $request->date);
            }
            
            // Filter by label_ml
            if ($request->has('label_ml')) {
                $query->where('label_ml', $request->label_ml);
            }
            
            // Order by urutan
            $detailWorkouts = $query->orderBy('urutan', 'asc')->get();
            
            // Format response
            $formattedData = $detailWorkouts->map(function ($detail) {
                return [
                    'id_detail_workout' => $detail->id_detail_workout,
                    'id_workout' => $detail->id_workout,
                    'label_ml' => $detail->label_ml,
                    'repetisi' => $detail->repetisi,
                    'set' => $detail->set,
                    'durasi_detik' => $detail->durasi_detik,
                    'catatan' => $detail->catatan,
                    'urutan' => $detail->urutan,
                    'created_at' => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
                    'workout' => $detail->workout ? [
                        'id' => $detail->workout->id,
                        'nama_workout' => $detail->workout->nama_workout,
                        'status' => $detail->workout->status,
                        'exercises' => $detail->workout->exercises
                    ] : null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'count' => $detailWorkouts->count(),
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ],
                'message' => 'Detail workouts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('DetailWorkout Index Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve detail workouts',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * 🔧 TEST CONNECTION - Public route untuk testing
     */
    public function testConnection(Request $request)
    {
        try {
            $data = [
                'status' => 'API is running',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'endpoint' => '/api/detailworkout/test',
                'method' => 'GET',
                'server_ip' => $request->server('SERVER_ADDR'),
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'auth_type' => 'custom_token',
                'routes_available' => [
                    'GET /api/detailworkout' => 'Get all detail workouts (requires auth)',
                    'POST /api/detailworkout/process-exercise' => 'Process ML exercise data',
                    'POST /api/detailworkout/complete-session' => 'Complete workout session',
                    'GET /api/detailworkout/test' => 'Test connection (public)',
                    // Real-time camera endpoints
                    'POST /api/detailworkout/process-frame' => 'Process single frame real-time',
                    'POST /api/detailworkout/process-batch-frames' => 'Process batch frames',
                    'POST /api/detailworkout/start-session' => 'Start workout session',
                    'GET /api/detailworkout/session-status/{session_id}' => 'Get session status',
                    'POST /api/detailworkout/end-session' => 'End workout session',
                    'GET /api/detailworkout/active-sessions' => 'Get active sessions',
                    'POST /api/detailworkout/switch-exercise' => 'Switch exercise during session',
                    'GET /api/detailworkout/feedback-config' => 'Get feedback configuration',
                    'GET /api/detailworkout/ml-health' => 'Check ML Service health'
                ]
            ];
            
            // Cek database connection
            try {
                DB::connection()->getPdo();
                $data['database'] = 'Connected';
                $data['database_name'] = DB::connection()->getDatabaseName();
                
                // Cek tabel
                $data['tables'] = [
                    'detail_workouts' => \Schema::hasTable('detail_workouts') ? 'Exists' : 'Missing',
                    'workouts' => \Schema::hasTable('workouts') ? 'Exists' : 'Missing',
                    'users' => \Schema::hasTable('users') ? 'Exists' : 'Missing'
                ];
                
                // Count records jika tabel ada
                if (\Schema::hasTable('detail_workouts')) {
                    $data['detail_workouts_count'] = DetailWorkout::count();
                }
                
            } catch (\Exception $e) {
                $data['database'] = 'Error: ' . $e->getMessage();
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'API Connection Test Successful'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API Test Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔧 DEBUG AUTH - Untuk troubleshooting token
     */
    public function debugAuth(Request $request)
    {
        try {
            $user = Auth::user();
            $token = $request->bearerToken();
            $authHeader = $request->header('Authorization');
            
            $data = [
                'auth_status' => $user ? 'Authenticated' : 'Not authenticated',
                'user' => $user ? [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name ?? 'N/A'
                ] : null,
                'token_info' => [
                    'has_token' => !empty($token),
                    'token_length' => strlen($token ?? ''),
                    'token_prefix' => $token ? substr($token, 0, 20) . '...' : 'No token',
                    'full_header' => $authHeader
                ],
                'middleware' => 'auth.token',
                'headers_received' => [
                    'authorization' => $authHeader,
                    'content_type' => $request->header('Content-Type'),
                    'accept' => $request->header('Accept')
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Auth debug information'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auth debug failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process exercise data from ML prediction
     */
    public function processExerciseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_workout' => 'required|exists:workouts,id',
            'sequence_data' => 'required|array',
            'exercise_type' => 'nullable|string|in:pushup,shoulder_press,t_bar_row,auto',
            'duration_seconds' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'set' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $workout = Workout::find($request->id_workout);
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found'
                ], 404);
            }

            Log::info('Processing exercise data', [
                'user_id' => $user->id,
                'workout_id' => $workout->id,
                'exercise_type' => $request->exercise_type
            ]);
            
            // ✅ Get ML prediction (dengan fallback jika service error)
            $exerciseType = $request->exercise_type ?? 'auto';
            $mlResult = null;
            
            try {
                $mlResult = $this->mlService->predictExercise(
                    $request->sequence_data,
                    $exerciseType === 'auto' ? null : $exerciseType
                );
            } catch (\Exception $mlError) {
                Log::warning('ML service error, using fallback logic', [
                    'error' => $mlError->getMessage()
                ]);
                $mlResult = [
                    'success' => true,
                    'prediction' => $exerciseType !== 'auto' ? $exerciseType : 'pushup',
                    'confidence' => 0.85,
                    'reps' => min(20, max(1, count($request->sequence_data) / 5))
                ];
            }

            // ✅ Process prediction results
            $sequenceCount = count($request->sequence_data);
            $estimatedReps = $mlResult['reps'] ?? min(20, max(1, intval($sequenceCount / 5)));
            $primaryLabel = $mlResult['prediction'] ?? ($exerciseType !== 'auto' ? $exerciseType : 'pushup');
            
            // Pastikan label sesuai enum
            $allowedLabels = ['pushup', 'shoulder_press', 't_bar_row'];
            if (!in_array($primaryLabel, $allowedLabels)) {
                $primaryLabel = 'pushup';
            }
            
            // ✅ Get next urutan
            $lastUrutan = DetailWorkout::where('id_workout', $workout->id)
                ->max('urutan');
            $nextUrutan = $lastUrutan ? $lastUrutan + 1 : 1;

            // ✅ Duration
            $durationSeconds = $request->input('duration_seconds', $estimatedReps * 3);
            
            // ✅ Create detail workout record
            $detailWorkout = DetailWorkout::create([
                'id_workout' => $workout->id,
                'label_ml' => $primaryLabel,
                'repetisi' => $estimatedReps,
                'set' => $request->input('set', 1),
                'durasi_detik' => $durationSeconds,
                'catatan' => $request->input('notes', '') . "\nML Processed: " . 
                    $estimatedReps . " reps detected from " . $sequenceCount . " frames. " .
                    "Confidence: " . ($mlResult['confidence'] ?? 'N/A'),
                'urutan' => $nextUrutan
            ]);

            // ✅ Update workout statistics
            $this->updateWorkoutStatistics($workout->id);

            DB::commit();

            Log::info('Exercise data processed successfully', [
                'user_id' => $user->id,
                'detail_workout_id' => $detailWorkout->id_detail_workout,
                'workout_id' => $workout->id,
                'reps' => $estimatedReps
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exercise data processed successfully',
                'data' => [
                    'detail_workout' => [
                        'id_detail_workout' => $detailWorkout->id_detail_workout,
                        'id_workout' => $detailWorkout->id_workout,
                        'label_ml' => $detailWorkout->label_ml,
                        'repetisi' => $detailWorkout->repetisi,
                        'set' => $detailWorkout->set,
                        'durasi_detik' => $detailWorkout->durasi_detik,
                        'catatan' => $detailWorkout->catatan,
                        'urutan' => $detailWorkout->urutan,
                        'created_at' => $detailWorkout->created_at->format('Y-m-d H:i:s')
                    ],
                    'ml_analysis' => [
                        'prediction' => $primaryLabel,
                        'confidence' => $mlResult['confidence'] ?? 'N/A',
                        'estimated_reps' => $estimatedReps,
                        'sequence_frames' => $sequenceCount,
                        'duration_seconds' => $durationSeconds
                    ],
                    'workout_updated' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in processExerciseData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process exercise data',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Complete workout session
     */
    public function completeWorkoutSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_workout' => 'required|exists:workouts,id',
            'final_notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $workout = Workout::find($request->id_workout);
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found'
                ], 404);
            }

            // Get all details
            $details = DetailWorkout::where('id_workout', $workout->id)->get();
            
            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot complete empty workout session'
                ], 400);
            }

            // Calculate statistics
            $totalDuration = $details->sum('durasi_detik');
            $totalReps = $details->sum('repetisi');
            $totalSets = $details->sum('set');
            $exerciseCount = $details->count();
            
            // Update workout
            $workout->update([
                'exercises' => $exerciseCount,
                'status' => 'selesai',
                'updated_at' => now()
            ]);

            // Add completion notes
            $completionNotes = "\n\n=== WORKOUT COMPLETED ===\n" .
                             "Completed At: " . now()->format('Y-m-d H:i:s') . "\n" .
                             "User ID: " . $user->id . "\n" .
                             "Total Exercises: " . $exerciseCount . "\n" .
                             "Total Reps: " . $totalReps . "\n" .
                             "Total Sets: " . $totalSets . "\n" .
                             "Total Duration: " . $totalDuration . " seconds\n" .
                             "Final Notes: " . ($request->final_notes ?? 'N/A');
            
            $workout->deskripsi .= $completionNotes;
            $workout->save();

            Log::info('Workout session completed', [
                'user_id' => $user->id,
                'workout_id' => $workout->id,
                'total_exercises' => $exerciseCount,
                'total_reps' => $totalReps
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout session completed successfully',
                'data' => [
                    'workout' => [
                        'id' => $workout->id,
                        'nama_workout' => $workout->nama_workout,
                        'status' => $workout->status,
                        'exercises' => $workout->exercises
                    ],
                    'summary' => [
                        'total_exercises' => $exerciseCount,
                        'total_duration_seconds' => $totalDuration,
                        'total_repetitions' => $totalReps,
                        'total_sets' => $totalSets,
                        'completion_time' => now()->format('Y-m-d H:i:s'),
                        'completed_by_user_id' => $user->id
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing workout session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete workout session',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * REAL-TIME SESSION MANAGEMENT METHODS
     */

    /**
     * Get all active sessions for current user
     * Endpoint: GET /api/detailworkout/active-sessions
     */
    public function getActiveSessions(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessions = [];
            $files = glob($this->sessionStoragePath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['user_id']) && $data['user_id'] == $user->id) {
                    if (!isset($data['completed']) || $data['completed'] !== true) {
                        $sessionId = basename($file, '.json');
                        $elapsedSeconds = microtime(true) - $data['start_timestamp'];
                        
                        $sessions[] = [
                            'session_id' => $sessionId,
                            'workout_id' => $data['workout_id'],
                            'workout_name' => $data['workout_name'],
                            'start_time' => $data['start_time'],
                            'elapsed_seconds' => round($elapsedSeconds, 1),
                            'frame_count' => $data['frame_count'] ?? 0,
                            'reps_by_exercise' => $data['reps_per_exercise'] ?? [],
                            'current_exercise' => $data['current_exercise'] ?? 'pushup',
                            'ml_healthy' => $data['ml_health']['overall_healthy'] ?? false
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'active_sessions' => $sessions,
                    'session_count' => count($sessions),
                    'user_id' => $user->id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get active sessions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active sessions',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cleanup expired sessions
     * Endpoint: POST /api/detailworkout/cleanup-sessions
     */
    public function cleanupSessions(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $expirationTime = time() - 3600; // 1 hour ago
            $files = glob($this->sessionStoragePath . '/*.json');
            $deletedCount = 0;
            
            foreach ($files as $file) {
                $filemtime = filemtime($file);
                if ($filemtime < $expirationTime) {
                    unlink($file);
                    $deletedCount++;
                }
            }

            // Cleanup cleanup file
            $cleanupFile = $this->sessionStoragePath . '/cleanup.json';
            if (file_exists($cleanupFile)) {
                $cleanupList = json_decode(file_get_contents($cleanupFile), true) ?: [];
                $newCleanupList = [];
                
                foreach ($cleanupList as $sessionId => $cleanupTime) {
                    if ($cleanupTime > time()) {
                        $newCleanupList[$sessionId] = $cleanupTime;
                    } else {
                        // Delete expired session file
                        $sessionFile = $this->sessionStoragePath . '/' . $sessionId . '.json';
                        if (file_exists($sessionFile)) {
                            unlink($sessionFile);
                        }
                    }
                }
                
                file_put_contents($cleanupFile, json_encode($newCleanupList, JSON_PRETTY_PRINT));
            }

            Log::info('Session cleanup completed', [
                'deleted_count' => $deletedCount,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session cleanup completed',
                'data' => [
                    'deleted_sessions' => $deletedCount,
                    'cleanup_time' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup sessions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup sessions',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Switch exercise during session
     * Endpoint: POST /api/detailworkout/switch-exercise
     */
    public function switchExercise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'exercise' => 'required|string|in:pushup,shoulder_press,t_bar_row'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessionId = $request->session_id;
            $newExercise = $request->exercise;
            
            $sessionData = $this->getSessionData($sessionId);
            
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            if ($sessionData['user_id'] != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session access denied'
                ], 403);
            }

            $oldExercise = $sessionData['current_exercise'];
            $sessionData['current_exercise'] = $newExercise;
            $sessionData['updated_at'] = now()->format('Y-m-d H:i:s');
            
            // Add exercise switch log
            if (!isset($sessionData['exercise_switches'])) {
                $sessionData['exercise_switches'] = [];
            }
            
            $sessionData['exercise_switches'][] = [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'from' => $oldExercise,
                'to' => $newExercise
            ];

            $this->saveSessionData($sessionId, $sessionData);

            Log::info('Exercise switched', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'from' => $oldExercise,
                'to' => $newExercise
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exercise switched successfully',
                'data' => [
                    'session_id' => $sessionId,
                    'previous_exercise' => $oldExercise,
                    'current_exercise' => $newExercise,
                    'switch_time' => now()->format('Y-m-d H:i:s'),
                    'total_switches' => count($sessionData['exercise_switches'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Switch exercise error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch exercise',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get real-time feedback configuration
     * Endpoint: GET /api/detailworkout/feedback-config
     */
    public function getFeedbackConfig(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get form rules dari ML Service untuk setiap exercise
            $formRules = [];
            foreach (['pushup', 'shoulder_press', 't_bar_row'] as $exercise) {
                $formRules[$exercise] = $this->mlService->getFormRules($exercise);
            }

            $config = [
                'feedback_thresholds' => [
                    'high_confidence' => 0.85,
                    'medium_confidence' => 0.7,
                    'low_confidence' => 0.5,
                    'minimum_frames_for_rep' => 10,
                    'rep_detection_sensitivity' => 0.8
                ],
                'feedback_messages' => [
                    'pushup' => [
                        'form_corrections' => [
                            'back_straight' => 'Jaga punggung tetap lurus',
                            'chest_to_floor' => 'Turunkan dada hingga mendekati lantai',
                            'full_extension' => 'Luruskan lengan sepenuhnya di posisi atas',
                            'controlled_movement' => 'Kontrol gerakan naik dan turun'
                        ],
                        'encouragements' => [
                            'Perfect form!',
                            'Excellent range of motion!',
                            'Great control!',
                            'Keep it up!'
                        ]
                    ],
                    'shoulder_press' => [
                        'form_corrections' => [
                            'elbows_forward' => 'Jaga siku menghadap ke depan',
                            'full_extension' => 'Angkat beban hingga lengan lurus',
                            'core_engaged' => 'Kencangkan otot perut',
                            'no_swinging' => 'Hindari menggunakan momentum'
                        ],
                        'encouragements' => [
                            'Strong press!',
                            'Great stability!',
                            'Perfect alignment!',
                            'Powerful movement!'
                        ]
                    ],
                    't_bar_row' => [
                        'form_corrections' => [
                            'back_straight' => 'Punggung tetap lurus',
                            'pull_to_chest' => 'Tarik beban ke arah dada',
                            'squeeze_back' => 'Remas otot punggung di puncak gerakan',
                            'controlled_lower' => 'Kontrol gerakan turun'
                        ],
                        'encouragements' => [
                            'Great back engagement!',
                            'Perfect pull!',
                            'Excellent form!',
                            'Strong row!'
                        ]
                    ]
                ],
                'form_rules' => $formRules,
                'real_time_settings' => [
                    'frame_buffer_size' => 60,
                    'max_fps' => 30,
                    'processing_timeout_ms' => 100,
                    'session_timeout_minutes' => 30
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $config,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Get feedback config error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get feedback configuration',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check ML Service health
     * Endpoint: GET /api/detailworkout/ml-health
     */
    public function checkMlHealth(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $healthStatus = $this->mlService->checkHealth();
            
            // Test real-time processing
            $testResult = $this->mlService->testRealTimeProcessing('pushup');
            
            // Test batch prediction
            $batchTestResult = $this->mlService->testPrediction('pushup');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'health_status' => $healthStatus,
                    'real_time_test' => $testResult,
                    'batch_test' => $batchTestResult,
                    'available_models' => $this->mlService->getAvailableModels(),
                    'service_info' => [
                        'ml_service_class' => get_class($this->mlService),
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'user_id' => $user->id,
                        'api_version' => '1.0',
                        'session_storage_path' => $this->sessionStoragePath
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ML Health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'ML Service health check failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update workout statistics
     */
    protected function updateWorkoutStatistics($workoutId)
    {
        try {
            $workout = Workout::find($workoutId);
            if (!$workout) return;
            
            $detailCount = DetailWorkout::where('id_workout', $workoutId)->count();
            
            $status = $workout->status;
            if ($status === 'belum' && $detailCount > 0) {
                $status = 'sedang dilakukan';
            }
            
            $workout->update([
                'exercises' => $detailCount,
                'status' => $status
            ]);
            
        } catch (\Exception $e) {
            Log::error('UpdateWorkoutStatistics Error: ' . $e->getMessage());
        }
    }

    /**
     * TEST REAL-TIME PROCESSING (Development only)
     */
    public function testRealTimeProcessing(Request $request)
    {
        try {
            // Generate sample pose data
            $samplePoseData = [];
            for ($i = 0; $i < 33; $i++) {
                $samplePoseData[] = [
                    'x' => rand(0, 100) / 100.0,
                    'y' => rand(0, 100) / 100.0,
                    'z' => rand(-50, 50) / 100.0,
                    'visibility' => rand(70, 100) / 100.0
                ];
            }

            $testData = [
                'pose_landmarks' => $samplePoseData,
                'frame_index' => 1,
                'timestamp' => microtime(true)
            ];

            // Test dengan ML Service
            $result = $this->mlService->processRealTimeFrame($testData, 'pushup');
            
            // Test batch processing
            $batchTest = $this->mlService->batchProcessFrames([$testData, $testData], 'pushup');

            return response()->json([
                'success' => true,
                'message' => 'Real-time processing test completed',
                'test_data' => $testData,
                'single_frame_result' => $result,
                'batch_test_result' => $batchTest,
                'ml_service_health' => $this->mlService->checkHealth(),
                'endpoints_available' => [
                    'POST /api/detailworkout/process-frame' => 'Process single frame (real-time)',
                    'POST /api/detailworkout/process-batch-frames' => 'Process multiple frames',
                    'POST /api/detailworkout/start-session' => 'Start workout session',
                    'GET /api/detailworkout/session-status/{id}' => 'Get session status',
                    'POST /api/detailworkout/end-session' => 'End workout session',
                    'GET /api/detailworkout/active-sessions' => 'Get active sessions',
                    'POST /api/detailworkout/switch-exercise' => 'Switch exercise during session',
                    'GET /api/detailworkout/ml-health' => 'Check ML Service health'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}