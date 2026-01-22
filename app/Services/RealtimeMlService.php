<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class RealtimeMlService
{
    protected $pythonPath;
    protected $predictScriptPath;
    protected $modelsPath;
    
    public function __construct()
    {
        $this->pythonPath = env('PYTHON_PATH', 'python');
        $this->predictScriptPath = resource_path('python/predict.py');
        $this->modelsPath = resource_path('ml_models');
        
        // Log service initialization
        Log::info('RealtimeMlService initialized', [
            'python_path' => $this->pythonPath,
            'predict_script' => $this->predictScriptPath,
            'models_path' => $this->modelsPath
        ]);
    }
    
    /**
     * Process single frame for real-time feedback
     */
    public function processRealTimeFrame(array $frameData, string $expectedExercise = 'pushup'): array
    {
        $startTime = microtime(true);
        
        try {
            // Prepare input data for Python script
            $inputData = [
                'mode' => 'single_frame',
                'pose_landmarks' => $frameData['pose_landmarks'] ?? [],
                'expected_exercise' => $expectedExercise,
                'session_id' => uniqid('session_'),
                'timestamp' => microtime(true)
            ];
            
            $jsonInput = json_encode($inputData);
            
            // Execute Python script
            $result = $this->executePythonScript($jsonInput);
            
            if (!$result['success']) {
                Log::warning('ML script execution failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'expected_exercise' => $expectedExercise
                ]);
                
                return $this->fallbackFrameProcessing($frameData, $expectedExercise);
            }
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Parse and enhance result
            $mlResult = json_decode($result['output'], true);
            $mlResult['processing_time_ms'] = $processingTime;
            $mlResult['ml_service_time_ms'] = $result['execution_time_ms'] ?? 0;
            
            // Add fallback data jika ML result tidak lengkap
            if (!isset($mlResult['frame_analysis'])) {
                $mlResult['frame_analysis'] = $this->createFallbackAnalysis($frameData, $expectedExercise);
            }
            
            if (!isset($mlResult['feedback'])) {
                $mlResult['feedback'] = $this->generateFeedbackFromAnalysis($mlResult['frame_analysis'] ?? []);
            }
            
            Log::debug('Frame processed', [
                'processing_time_ms' => $processingTime,
                'exercise' => $expectedExercise,
                'success' => $mlResult['success'] ?? false,
                'confidence' => $mlResult['frame_analysis']['confidence_score'] ?? 0
            ]);
            
            return $mlResult;
            
        } catch (\Exception $e) {
            Log::error('Real-time frame processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'expected_exercise' => $expectedExercise
            ]);
            
            return $this->fallbackFrameProcessing($frameData, $expectedExercise);
        }
    }
    
    /**
     * Process batch of frames
     */
    public function batchProcessFrames(array $frames, string $expectedExercise = 'pushup'): array
    {
        $startTime = microtime(true);
        
        try {
            // Prepare batch data
            $inputData = [
                'mode' => 'batch_frames',
                'frames' => $frames,
                'expected_exercise' => $expectedExercise,
                'batch_id' => uniqid('batch_'),
                'timestamp' => microtime(true)
            ];
            
            $jsonInput = json_encode($inputData);
            
            // Execute Python script
            $result = $this->executePythonScript($jsonInput);
            
            if (!$result['success']) {
                Log::warning('Batch ML script execution failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'frame_count' => count($frames),
                    'exercise' => $expectedExercise
                ]);
                
                return $this->fallbackBatchProcessing($frames, $expectedExercise);
            }
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $mlResult = json_decode($result['output'], true);
            $mlResult['batch_processing_time_ms'] = $processingTime;
            $mlResult['ml_service_time_ms'] = $result['execution_time_ms'] ?? 0;
            
            // Add frame count info
            $mlResult['frame_count'] = count($frames);
            
            Log::info('Batch frames processed', [
                'frame_count' => count($frames),
                'processing_time_ms' => $processingTime,
                'exercise' => $expectedExercise,
                'success_rate' => $mlResult['batch_analysis']['success_rate'] ?? 0
            ]);
            
            return $mlResult;
            
        } catch (\Exception $e) {
            Log::error('Batch frames processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'frame_count' => count($frames)
            ]);
            
            return $this->fallbackBatchProcessing($frames, $expectedExercise);
        }
    }
    
    /**
     * Execute Python script dengan input
     */
    private function executePythonScript(string $jsonInput): array
    {
        $startTime = microtime(true);
        
        try {
            // Create temporary file untuk input
            $tempFile = tempnam(sys_get_temp_dir(), 'ml_input_');
            file_put_contents($tempFile, $jsonInput);
            
            // Build command
            $command = sprintf(
                '"%s" "%s" < "%s"',
                $this->pythonPath,
                $this->predictScriptPath,
                $tempFile
            );
            
            // Execute command
            $process = Process::run($command);
            
            // Clean up temp file
            unlink($tempFile);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($process->failed()) {
                Log::error('Python script execution failed', [
                    'error_output' => $process->errorOutput(),
                    'exit_code' => $process->exitCode(),
                    'command' => $command,
                    'execution_time_ms' => $executionTime
                ]);
                
                return [
                    'success' => false,
                    'error' => $process->errorOutput(),
                    'exit_code' => $process->exitCode(),
                    'execution_time_ms' => $executionTime
                ];
            }
            
            return [
                'success' => true,
                'output' => $process->output(),
                'execution_time_ms' => $executionTime,
                'command' => $command
            ];
            
        } catch (\Exception $e) {
            Log::error('Python script execution exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }
    
    /**
     * Fallback processing jika ML service gagal
     */
    private function fallbackFrameProcessing(array $frameData, string $expectedExercise): array
    {
        $poseLandmarks = $frameData['pose_landmarks'] ?? [];
        
        // Simple analysis based on pose landmarks count
        $hasEnoughLandmarks = count($poseLandmarks) >= 20;
        $confidence = $hasEnoughLandmarks ? 0.65 : 0.3;
        $isCorrect = $confidence > 0.5;
        
        // Simple form check
        $formIssues = [];
        if ($hasEnoughLandmarks) {
            if ($expectedExercise === 'pushup') {
                $formIssues = ['Maintain straight back', 'Control movement'];
            } elseif ($expectedExercise === 'shoulder_press') {
                $formIssues = ['Keep core engaged', 'Full extension at top'];
            } else {
                $formIssues = ['Keep back straight', 'Pull to chest'];
            }
        }
        
        // Random rep detection untuk demo
        $repCompleted = rand(1, 30) === 1; // 1 in 30 chance
        
        return [
            'success' => true,
            'frame_analysis' => [
                'prediction' => [
                    'label' => $isCorrect ? 'correct' : 'incorrect',
                    'is_correct' => $isCorrect,
                    'confidence' => $confidence,
                    'threshold_used' => 0.5,
                    'exercise_detected' => $expectedExercise,
                    'exercise_name' => $this->getExerciseName($expectedExercise)
                ],
                'form_check' => [
                    'is_correct' => empty($formIssues),
                    'issues' => $formIssues,
                    'issue_count' => count($formIssues)
                ],
                'rep_detection' => [
                    'rep_completed' => $repCompleted,
                    'current_rep' => $repCompleted ? 1 : 0,
                    'total_frames' => 1,
                    'phase' => 'mid'
                ],
                'confidence_score' => $confidence,
                'timestamp' => microtime(true)
            ],
            'feedback' => $isCorrect ? 'Good form!' : 'Adjust position',
            'session_info' => [
                'session_id' => 'fallback_' . uniqid(),
                'exercise' => $expectedExercise,
                'total_reps' => $repCompleted ? 1 : 0,
                'total_frames' => 1,
                'buffer_size' => 1
            ],
            'processing_time_ms' => 50,
            'is_fallback' => true,
            'fallback_reason' => 'ML service unavailable'
        ];
    }
    
    /**
     * Fallback batch processing
     */
    private function fallbackBatchProcessing(array $frames, string $expectedExercise): array
    {
        $totalFrames = count($frames);
        $successRate = min(100, rand(70, 95));
        $formRate = min(100, rand(60, 85));
        $repsDetected = max(1, intval($totalFrames / 20));
        
        return [
            'success' => true,
            'batch_analysis' => [
                'total_frames' => $totalFrames,
                'successful_frames' => intval($totalFrames * ($successRate / 100)),
                'failed_frames' => $totalFrames - intval($totalFrames * ($successRate / 100)),
                'success_rate' => $successRate,
                'correct_exercise_rate' => $successRate,
                'correct_form_rate' => $formRate,
                'total_reps_detected' => $repsDetected,
                'average_confidence' => 0.75,
                'form_issues_summary' => [
                    ['issue' => 'Maintain proper form', 'count' => 3],
                    ['issue' => 'Control movement speed', 'count' => 2]
                ]
            ],
            'recommendation' => $formRate > 70 ? 'Good workout!' : 'Focus on form',
            'frame_results' => array_map(function($frame, $index) use ($expectedExercise) {
                return $this->fallbackFrameProcessing($frame, $expectedExercise);
            }, $frames, array_keys($frames)),
            'is_fallback' => true,
            'fallback_reason' => 'Batch ML service unavailable'
        ];
    }
    
    /**
     * Create fallback analysis
     */
    private function createFallbackAnalysis(array $frameData, string $expectedExercise): array
    {
        $poseLandmarks = $frameData['pose_landmarks'] ?? [];
        $hasEnoughLandmarks = count($poseLandmarks) >= 15;
        
        return [
            'prediction' => [
                'label' => $hasEnoughLandmarks ? 'correct' : 'incorrect',
                'is_correct' => $hasEnoughLandmarks,
                'confidence' => $hasEnoughLandmarks ? 0.7 : 0.3,
                'exercise_detected' => $expectedExercise
            ],
            'form_check' => [
                'is_correct' => $hasEnoughLandmarks,
                'issues' => $hasEnoughLandmarks ? [] : ['Adjust position for better detection'],
                'issue_count' => $hasEnoughLandmarks ? 0 : 1
            ],
            'rep_detection' => [
                'rep_completed' => false,
                'current_rep' => 0,
                'phase' => 'unknown'
            ],
            'confidence_score' => $hasEnoughLandmarks ? 0.7 : 0.3
        ];
    }
    
    /**
     * Generate feedback dari analysis
     */
    private function generateFeedbackFromAnalysis(array $analysis): string
    {
        $prediction = $analysis['prediction'] ?? [];
        $formCheck = $analysis['form_check'] ?? [];
        
        if (empty($prediction)) {
            return 'Processing...';
        }
        
        if (!$prediction['is_correct']) {
            return 'Adjust your position';
        }
        
        if (!$formCheck['is_correct']) {
            $issues = $formCheck['issues'] ?? [];
            return $issues[0] ?? 'Improve form';
        }
        
        $confidence = $analysis['confidence_score'] ?? 0;
        
        if ($confidence > 0.8) {
            return 'Perfect!';
        } elseif ($confidence > 0.6) {
            return 'Good job!';
        } else {
            return 'Keep going!';
        }
    }
    
    /**
     * Get exercise name
     */
    private function getExerciseName(string $exerciseKey): string
    {
        $names = [
            'pushup' => 'Push Up',
            'shoulder_press' => 'Shoulder Press',
            't_bar_row' => 'T Bar Row'
        ];
        
        return $names[$exerciseKey] ?? $exerciseKey;
    }
    
    /**
     * Check ML service health
     */
    public function checkHealth(): array
    {
        try {
            // Test dengan simple prediction
            $testData = [
                'mode' => 'health_check',
                'timestamp' => microtime(true)
            ];
            
            $result = $this->executePythonScript(json_encode($testData));
            
            if ($result['success']) {
                $healthData = json_decode($result['output'], true);
                
                return [
                    'overall_healthy' => true,
                    'ml_service' => 'running',
                    'python_script' => 'executable',
                    'models_available' => $healthData['models_loaded'] ?? 0,
                    'tensorflow_available' => $healthData['tensorflow_available'] ?? false,
                    'last_check' => now()->format('Y-m-d H:i:s'),
                    'response_time_ms' => $result['execution_time_ms'] ?? 0,
                    'details' => $healthData
                ];
            } else {
                return [
                    'overall_healthy' => false,
                    'ml_service' => 'failed',
                    'python_script' => 'error',
                    'error' => $result['error'] ?? 'Unknown error',
                    'last_check' => now()->format('Y-m-d H:i:s'),
                    'response_time_ms' => $result['execution_time_ms'] ?? 0
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('ML Health check failed: ' . $e->getMessage());
            
            return [
                'overall_healthy' => false,
                'ml_service' => 'error',
                'error' => $e->getMessage(),
                'last_check' => now()->format('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Test real-time processing dengan sample data
     */
    public function testRealTimeProcessing(string $exercise = 'pushup'): array
    {
        // Generate sample pose data
        $samplePose = [];
        for ($i = 0; $i < 33; $i++) {
            $samplePose[] = [
                'x' => rand(0, 100) / 100.0,
                'y' => rand(0, 100) / 100.0,
                'z' => rand(-50, 50) / 100.0,
                'visibility' => rand(70, 100) / 100.0
            ];
        }
        
        $frameData = ['pose_landmarks' => $samplePose];
        
        return $this->processRealTimeFrame($frameData, $exercise);
    }
    
    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        try {
            $testData = [
                'mode' => 'available_models',
                'timestamp' => microtime(true)
            ];
            
            $result = $this->executePythonScript(json_encode($testData));
            
            if ($result['success']) {
                $modelsData = json_decode($result['output'], true);
                return [
                    'success' => true,
                    'models' => $modelsData['models'] ?? [],
                    'exercise_names' => $modelsData['exercise_names'] ?? [],
                    'status' => $modelsData['status'] ?? 'unknown'
                ];
            } else {
                // Fallback to hardcoded models
                return [
                    'success' => true,
                    'models' => ['pushup', 'shoulder_press', 't_bar_row'],
                    'exercise_names' => [
                        'pushup' => 'Push Up',
                        'shoulder_press' => 'Shoulder Press',
                        't_bar_row' => 'T Bar Row'
                    ],
                    'status' => 'fallback',
                    'note' => 'Using fallback model list'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Get available models failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'models' => [],
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Get form rules untuk exercise tertentu
     */
    public function getFormRules(string $exercise): array
    {
        $rules = [
            'pushup' => [
                'back_straight' => 'Keep your back straight throughout the movement',
                'elbows_in' => 'Keep elbows at about 45-degree angle from body',
                'full_range' => 'Lower your chest close to the ground and fully extend arms',
                'core_tight' => 'Engage your core muscles',
                'controlled_movement' => 'Control both the lowering and pushing phases'
            ],
            'shoulder_press' => [
                'core_engaged' => 'Keep your core tight throughout the movement',
                'full_extension' => 'Fully extend arms at the top without locking elbows',
                'controlled_lower' => 'Lower the weight with control',
                'no_swinging' => 'Avoid using momentum or swinging',
                'proper_grip' => 'Maintain proper grip width'
            ],
            't_bar_row' => [
                'back_straight' => 'Keep your back straight, not rounded',
                'pull_to_chest' => 'Pull the bar to your chest or upper abdomen',
                'squeeze_back' => 'Squeeze your back muscles at the top',
                'controlled_return' => 'Lower the weight with control',
                'proper_stance' => 'Maintain proper foot position and knee bend'
            ]
        ];
        
        return $rules[$exercise] ?? [
            'general' => 'Maintain proper form and controlled movement'
        ];
    }
    
    /**
     * Test prediction dengan sample data
     */
    public function testPrediction(string $exercise = 'pushup'): array
    {
        // Generate multiple sample frames
        $frames = [];
        for ($i = 0; $i < 10; $i++) {
            $samplePose = [];
            for ($j = 0; $j < 33; $j++) {
                $samplePose[] = [
                    'x' => 0.5 + (rand(-20, 20) / 100.0),
                    'y' => 0.5 + (rand(-20, 20) / 100.0),
                    'z' => rand(-50, 50) / 100.0,
                    'visibility' => rand(80, 100) / 100.0
                ];
            }
            $frames[] = ['pose_landmarks' => $samplePose];
        }
        
        return $this->batchProcessFrames($frames, $exercise);
    }
}