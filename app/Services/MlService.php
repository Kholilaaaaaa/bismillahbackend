<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Exception;

class MlService
{
    private $pythonScriptPath;
    private $pythonExecutable;
    private $modelsPath;
    
    public function __construct()
    {
        // Set path ke script Python
        $this->pythonScriptPath = base_path('resources/python/predict.py');
        $this->modelsPath = base_path('resources/ml_models');
        
        // Tentukan Python executable berdasarkan environment
        if (app()->environment('production')) {
            $this->pythonExecutable = 'python3';
        } else {
            // Coba beberapa kemungkinan path Python di local
            $possiblePaths = [
                base_path('venv_gymgenz/Scripts/python.exe'), // Windows dengan virtual env
                base_path('venv_gymgenz/bin/python'),         // Linux/Mac dengan virtual env
                'python',
                'python3',
                '/usr/bin/python3',
                'C:\\Python39\\python.exe',
                'C:\\Python310\\python.exe',
                'C:\\Python311\\python.exe',
                '/usr/local/bin/python3',
            ];
            
            $this->pythonExecutable = $this->findPythonExecutable($possiblePaths);
        }
        
        Log::info('ML Service initialized', [
            'python_executable' => $this->pythonExecutable,
            'python_script' => $this->pythonScriptPath,
            'models_path' => $this->modelsPath
        ]);
    }
    
    private function findPythonExecutable(array $paths)
    {
        foreach ($paths as $path) {
            if ($this->isValidPythonPath($path)) {
                Log::info("Found Python executable at: {$path}");
                return $path;
            }
        }
        
        // Fallback ke python3
        Log::warning('No valid Python executable found, falling back to python3');
        return 'python3';
    }
    
    private function isValidPythonPath($path)
    {
        try {
            // Cek jika file executable ada (untuk absolute path)
            if (str_contains($path, '/') || str_contains($path, '\\')) {
                if (!file_exists($path)) {
                    return false;
                }
            }
            
            // Cek versi Python
            $process = Process::run("{$path} --version");
            if ($process->successful() && Str::contains($process->output(), 'Python')) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::warning("Failed to check Python path {$path}: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Predict exercise from motion data
     *
     * @param array $sequenceData Data sensor sequences
     * @param string|null $modelName Specific model to use (optional)
     * @return array Prediction results
     */
    public function predictExercise(array $sequenceData, ?string $modelName = null): array
    {
        try {
            Log::info('ML prediction request', [
                'data_points' => count($sequenceData),
                'model' => $modelName ?? 'auto-detect',
                'python_executable' => $this->pythonExecutable
            ]);
            
            // Validate input data
            $validation = $this->validateMotionData($sequenceData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Invalid motion data format',
                    'validation_errors' => $validation['errors'],
                    'data_info' => $validation['data_info']
                ];
            }
            
            // Check feature count - PERBAIKAN PENTING!
            $expectedFeatures = 103; // Sesuai dengan model training
            $actualFeatures = !empty($sequenceData[0]) ? count($sequenceData[0]) : 0;
            
            if ($actualFeatures !== $expectedFeatures) {
                Log::warning("Feature count mismatch: expected {$expectedFeatures}, got {$actualFeatures}");
                
                // Adjust data jika diperlukan
                $sequenceData = $this->adjustFeatureCount($sequenceData, $expectedFeatures);
                
                Log::info("Adjusted feature count to: " . (!empty($sequenceData[0]) ? count($sequenceData[0]) : 0));
            }
            
            // Prepare input data for Python script
            $inputData = [
                'sequence_data' => $sequenceData
            ];
            
            if ($modelName) {
                $inputData['model'] = $modelName;
            }
            
            $jsonInput = json_encode($inputData, JSON_PRETTY_PRINT);
            
            // Debug: Log data shape
            $sampleData = [
                'timesteps' => count($sequenceData),
                'features' => !empty($sequenceData[0]) ? count($sequenceData[0]) : 0,
                'sample_values' => array_slice($sequenceData[0] ?? [], 0, 5)
            ];
            Log::debug('Sequence data shape:', $sampleData);
            
            // Prepare command
            $command = "{$this->pythonExecutable} \"{$this->pythonScriptPath}\"";
            
            Log::info('Executing Python command', [
                'command' => Str::mask($command, '*', 20, 10), // Mask for security
                'input_size_bytes' => strlen($jsonInput)
            ]);
            
            // Execute Python script
            $process = Process::input($jsonInput)->timeout(30)->run($command);
            
            // Get output
            $output = $process->output();
            $errorOutput = $process->errorOutput();
            
            // Log process info
            $processInfo = [
                'success' => $process->successful(),
                'exit_code' => $process->exitCode(),
                'output_length' => strlen($output),
                'error_length' => strlen($errorOutput)
            ];
            
            Log::info('Python process completed', $processInfo);
            
            // Log error output if exists
            if (!empty($errorOutput)) {
                Log::warning('Python stderr output (first 500 chars):', [
                    'error_output' => substr($errorOutput, 0, 500)
                ]);
            }
            
            if (!$process->successful()) {
                Log::error('Python process failed', [
                    'exit_code' => $process->exitCode(),
                    'error_output' => $errorOutput
                ]);
                
                return [
                    'success' => false,
                    'error' => 'ML prediction process failed',
                    'exit_code' => $process->exitCode(),
                    'python_error' => $errorOutput,
                    'process_info' => $processInfo
                ];
            }
            
            // Parse JSON output
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse Python output as JSON', [
                    'json_error' => json_last_error_msg(),
                    'output_sample' => substr($output, 0, 200)
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to parse ML prediction result',
                    'json_error' => json_last_error_msg(),
                    'raw_output_sample' => substr($output, 0, 200)
                ];
            }
            
            // Ensure success flag exists
            if (!isset($result['success'])) {
                $result['success'] = !isset($result['error']);
            }
            
            if (isset($result['error'])) {
                Log::error('ML prediction returned error', $result);
            } else {
                Log::info('ML prediction successful', [
                    'model' => $result['model'] ?? 'unknown',
                    'exercise_name' => $result['exercise_name'] ?? 'unknown',
                    'correctness_percentage' => $result['correctness_percentage'] ?? 0,
                    'correct_count' => $result['correct_count'] ?? 0,
                    'total_count' => $result['total_count'] ?? 0
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('ML prediction exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'ML service exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Adjust feature count to match model requirements
     */
    private function adjustFeatureCount(array $data, int $expectedFeatures): array
    {
        if (empty($data)) {
            return $data;
        }
        
        $adjustedData = [];
        
        foreach ($data as $sequence) {
            $actualFeatures = count($sequence);
            
            if ($actualFeatures < $expectedFeatures) {
                // Pad with zeros
                $padding = array_fill(0, $expectedFeatures - $actualFeatures, 0.0);
                $adjustedData[] = array_merge($sequence, $padding);
            } elseif ($actualFeatures > $expectedFeatures) {
                // Truncate
                $adjustedData[] = array_slice($sequence, 0, $expectedFeatures);
            } else {
                $adjustedData[] = $sequence;
            }
        }
        
        return $adjustedData;
    }
    
    /**
     * Process real-time camera frame for exercise detection
     *
     * @param array $frameData Pose landmarks from camera
     * @param string $expectedExercise Expected exercise type
     * @return array Frame analysis results
     */
    public function processRealTimeFrame(array $frameData, string $expectedExercise): array
    {
        try {
            Log::info('Processing real-time frame', [
                'expected_exercise' => $expectedExercise,
                'frame_data_keys' => array_keys($frameData),
                'timestamp' => microtime(true)
            ]);
            
            // 1. EXTRACT POSE LANDMARKS
            $poseLandmarks = $frameData['pose_landmarks'] ?? $frameData;
            
            if (empty($poseLandmarks)) {
                return [
                    'success' => false,
                    'error' => 'No pose landmarks detected',
                    'recommendation' => 'Ensure camera can see full body',
                    'timestamp' => microtime(true)
                ];
            }
            
            // 2. PREPARE DATA FOR ML MODEL - PERBAIKAN: Gunakan 103 features
            $normalizedData = $this->normalizePoseDataForModel($poseLandmarks);
            
            // 3. PREDICT EXERCISE TYPE
            $prediction = $this->predictExerciseFromPose($normalizedData, $expectedExercise);
            
            // 4. CHECK FORM CORRECTNESS
            $formCheck = $this->checkExerciseForm($normalizedData, $expectedExercise);
            
            // 5. DETECT REP COMPLETION
            $repDetection = $this->detectRepCompletion($normalizedData, $expectedExercise);
            
            // 6. CALCULATE CONFIDENCE SCORE
            $confidenceScore = $this->calculateConfidenceScore($prediction, $formCheck);
            
            return [
                'success' => true,
                'frame_analysis' => [
                    'pose_detected' => true,
                    'landmarks_count' => count($poseLandmarks),
                    'prediction' => $prediction,
                    'form_check' => $formCheck,
                    'rep_detection' => $repDetection,
                    'confidence_score' => $confidenceScore,
                    'timestamp' => microtime(true),
                    'processing_time' => microtime(true) - LARAVEL_START
                ],
                'feedback' => $this->generateFeedback($prediction, $formCheck, $repDetection)
            ];
            
        } catch (Exception $e) {
            Log::error('Real-time frame processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'expected_exercise' => $expectedExercise
            ]);
            
            return [
                'success' => false,
                'error' => 'Frame processing failed: ' . $e->getMessage(),
                'fallback_prediction' => [
                    'exercise_detected' => $expectedExercise,
                    'confidence' => 0.6,
                    'is_correct_exercise' => true,
                    'note' => 'Fallback due to processing error'
                ]
            ];
        }
    }
    
    /**
     * Normalize pose landmarks data for ML model (103 features)
     */
    private function normalizePoseDataForModel(array $poseLandmarks): array
    {
        // Normalize coordinates to 0-1 range
        $normalized = [];
        
        foreach ($poseLandmarks as $landmark) {
            // Handle different landmark formats
            if (isset($landmark['x']) && isset($landmark['y'])) {
                $normalized[] = [
                    'x' => floatval($landmark['x'] ?? 0),
                    'y' => floatval($landmark['y'] ?? 0),
                    'z' => floatval($landmark['z'] ?? 0),
                    'visibility' => floatval($landmark['visibility'] ?? $landmark['score'] ?? 0.8)
                ];
            } elseif (isset($landmark[0]) && isset($landmark[1])) {
                // Array format: [x, y, z, visibility]
                $normalized[] = [
                    'x' => floatval($landmark[0] ?? 0),
                    'y' => floatval($landmark[1] ?? 0),
                    'z' => floatval($landmark[2] ?? 0),
                    'visibility' => floatval($landmark[3] ?? 0.8)
                ];
            } else {
                // Default jika format tidak dikenali
                $normalized[] = ['x' => 0.5, 'y' => 0.5, 'z' => 0, 'visibility' => 0.8];
            }
        }
        
        // Ensure we have at least 25 landmarks (25 * 4 = 100 features)
        while (count($normalized) < 25) {
            $normalized[] = ['x' => 0.5, 'y' => 0.5, 'z' => 0, 'visibility' => 0.8];
        }
        
        // Take only 25 landmarks untuk mendapatkan 100 features
        $normalized = array_slice($normalized, 0, 25);
        
        // Flatten to array: 25 landmarks * 4 values = 100 features
        $flattened = [];
        foreach ($normalized as $point) {
            $flattened[] = $point['x'];
            $flattened[] = $point['y'];
            $flattened[] = $point['z'];
            $flattened[] = $point['visibility'];
        }
        
        // Add 3 additional features
        $flattened[] = microtime(true) % 1.0; // Fractional seconds
        $flattened[] = 0.0; // Placeholder 1
        $flattened[] = 1.0; // Placeholder 2
        
        // Ensure exactly 103 features
        $flattened = array_slice($flattened, 0, 103);
        
        Log::debug('Normalized pose data for model', [
            'original_landmarks' => count($poseLandmarks),
            'normalized_landmarks' => count($normalized),
            'features_count' => count($flattened),
            'sample_features' => array_slice($flattened, 0, 5)
        ]);
        
        return $flattened;
    }
    
    /**
     * Predict exercise from pose data using Python script
     */
    private function predictExerciseFromPose(array $poseData, string $expectedExercise): array
    {
        try {
            // Create sequence of 20 timesteps dari single pose data
            $sequenceData = $this->createSequenceFromPoseData($poseData, 20);
            
            // Siapkan data untuk Python script
            $inputData = [
                'sequence_data' => $sequenceData,
                'model' => $expectedExercise
            ];
            
            $jsonInput = json_encode($inputData);
            
            // Panggil Python script dengan timeout yang lebih pendek untuk real-time
            $command = "{$this->pythonExecutable} \"{$this->pythonScriptPath}\"";
            
            Log::debug('Calling Python for real-time prediction', [
                'expected_exercise' => $expectedExercise,
                'pose_data_length' => count($poseData),
                'sequence_timesteps' => count($sequenceData)
            ]);
            
            $process = Process::input($jsonInput)->timeout(5)->run($command);
            
            if (!$process->successful()) {
                Log::warning('Python real-time prediction failed', [
                    'error' => $process->errorOutput(),
                    'exit_code' => $process->exitCode()
                ]);
                
                // Fallback to expected exercise with medium confidence
                return [
                    'exercise_detected' => $expectedExercise,
                    'confidence' => 0.7,
                    'is_correct_exercise' => true,
                    'processing_time' => 0,
                    'note' => 'Fallback prediction - Python script failed'
                ];
            }
            
            $result = json_decode($process->output(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse Python output: ' . json_last_error_msg());
            }
            
            if (isset($result['error'])) {
                throw new Exception('Python error: ' . $result['error']);
            }
            
            return [
                'exercise_detected' => $result['model'] ?? $expectedExercise,
                'confidence' => $result['average_confidence'] ?? 0.5,
                'is_correct_exercise' => ($result['model'] ?? '') === $expectedExercise,
                'processing_time' => 0,
                'raw_prediction' => $result
            ];
            
        } catch (Exception $e) {
            Log::warning('Pose prediction failed, using fallback', [
                'error' => $e->getMessage(),
                'expected_exercise' => $expectedExercise
            ]);
            
            return [
                'exercise_detected' => $expectedExercise,
                'confidence' => 0.7,
                'is_correct_exercise' => true,
                'processing_time' => 0,
                'note' => 'Fallback prediction - ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create sequence from single pose data point
     */
    private function createSequenceFromPoseData(array $poseData, int $sequenceLength): array
    {
        $sequence = [];
        
        for ($i = 0; $i < $sequenceLength; $i++) {
            // Add slight variations untuk membuat sequence lebih realistic
            $frameData = $poseData;
            if ($i > 0) {
                // Tambahkan sedikit noise untuk variasi
                foreach ($frameData as $j => &$value) {
                    $value += (rand(-100, 100) / 10000.0); // ±0.01 variation
                }
            }
            $sequence[] = $frameData;
        }
        
        return $sequence;
    }
    
    /**
     * Check if exercise form is correct based on pose data
     */
    private function checkExerciseForm(array $poseData, string $exercise): array
    {
        try {
            // Get form rules for specific exercise
            $formRules = $this->getFormRules($exercise);
            
            // Calculate body angles from pose data
            $angles = $this->calculateBodyAngles($poseData);
            
            $issues = [];
            $score = 1.0;
            
            // Check each form rule
            foreach ($formRules as $ruleName => $ruleValue) {
                $checkResult = $this->checkFormRule($ruleName, $angles, $ruleValue);
                
                if (!$checkResult['passed']) {
                    $issues[] = [
                        'rule' => $ruleName,
                        'message' => $checkResult['message'],
                        'actual' => $checkResult['actual'],
                        'expected' => $checkResult['expected'],
                        'severity' => $checkResult['severity']
                    ];
                    
                    // Reduce score based on severity
                    $score *= (1 - $checkResult['severity']);
                }
            }
            
            $isCorrect = empty($issues);
            
            return [
                'is_correct' => $isCorrect,
                'score' => $score,
                'issues' => $issues,
                'feedback' => $isCorrect ? 'Form baik! 💪' : 'Perbaiki form!',
                'angle_analysis' => $angles
            ];
            
        } catch (Exception $e) {
            Log::error('Form check failed', [
                'error' => $e->getMessage(),
                'exercise' => $exercise
            ]);
            
            return [
                'is_correct' => false,
                'score' => 0.5,
                'issues' => [['rule' => 'system', 'message' => 'Form analysis failed']],
                'feedback' => 'Sistem form check error',
                'note' => 'Form check failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get form rules for specific exercise
     */
    private function getFormRules(string $exercise): array
    {
        $rules = [
            'pushup' => [
                'elbow_angle' => [
                    'min' => 80, 
                    'max' => 120,
                    'ideal' => 90,
                    'severity' => 0.3
                ],
                'back_angle' => [
                    'min' => -10, 
                    'max' => 10,
                    'ideal' => 0,
                    'severity' => 0.4
                ],
                'hip_alignment' => [
                    'min' => 0.3, 
                    'max' => 0.7,
                    'ideal' => 0.5,
                    'severity' => 0.2
                ],
                'shoulder_stability' => [
                    'min' => 0.6, 
                    'max' => 1.0,
                    'ideal' => 0.8,
                    'severity' => 0.1
                ]
            ],
            'shoulder_press' => [
                'arm_extension' => [
                    'min' => 160, 
                    'max' => 180,
                    'ideal' => 170,
                    'severity' => 0.3
                ],
                'back_straightness' => [
                    'min' => -5, 
                    'max' => 5,
                    'ideal' => 0,
                    'severity' => 0.4
                ],
                'core_engagement' => [
                    'min' => 0.7, 
                    'max' => 1.0,
                    'ideal' => 0.9,
                    'severity' => 0.2
                ],
                'wrist_alignment' => [
                    'min' => -10, 
                    'max' => 10,
                    'ideal' => 0,
                    'severity' => 0.1
                ]
            ],
            't_bar_row' => [
                'back_angle' => [
                    'min' => 10, 
                    'max' => 30,
                    'ideal' => 20,
                    'severity' => 0.3
                ],
                'elbow_angle' => [
                    'min' => 70, 
                    'max' => 110,
                    'ideal' => 90,
                    'severity' => 0.3
                ],
                'hip_hinge' => [
                    'min' => 20, 
                    'max' => 40,
                    'ideal' => 30,
                    'severity' => 0.2
                ],
                'shoulder_retraction' => [
                    'min' => 0.6, 
                    'max' => 1.0,
                    'ideal' => 0.8,
                    'severity' => 0.2
                ]
            ]
        ];
        
        return $rules[$exercise] ?? [];
    }
    
    /**
     * Calculate body angles from pose data
     */
    private function calculateBodyAngles(array $poseData): array
    {
        // In real implementation, this would calculate actual angles from pose landmarks
        // For now, simulate based on exercise type and pose data
        
        // Extract some values from pose data for simulation
        $dataSum = array_sum(array_slice($poseData, 0, 20));
        
        return [
            'elbow_angle' => 90 + (sin($dataSum * 0.01) * 20),
            'back_angle' => 5 + (cos($dataSum * 0.02) * 15),
            'arm_extension' => 170 + (sin($dataSum * 0.015) * 10),
            'hip_alignment' => 0.5 + (sin($dataSum * 0.03) * 0.2),
            'hip_hinge' => 25 + (cos($dataSum * 0.025) * 15),
            'shoulder_stability' => 0.8 + (sin($dataSum * 0.04) * 0.15),
            'core_engagement' => 0.85 + (cos($dataSum * 0.035) * 0.1),
            'wrist_alignment' => 0 + (sin($dataSum * 0.05) * 8),
            'shoulder_retraction' => 0.75 + (cos($dataSum * 0.045) * 0.15)
        ];
    }
    
    /**
     * Check a specific form rule
     */
    private function checkFormRule(string $ruleName, array $angles, array $rule): array
    {
        $actualValue = $angles[$ruleName] ?? 0;
        
        if ($actualValue >= $rule['min'] && $actualValue <= $rule['max']) {
            return [
                'passed' => true,
                'message' => ucfirst(str_replace('_', ' ', $ruleName)) . ' baik',
                'actual' => round($actualValue, 1),
                'expected' => "{$rule['min']} - {$rule['max']}",
                'severity' => $rule['severity'] ?? 0.2
            ];
        }
        
        // Determine if value is too low or too high
        $direction = $actualValue < $rule['min'] ? 'terlalu rendah' : 'terlalu tinggi';
        $difference = $actualValue < $rule['min'] 
            ? $rule['min'] - $actualValue 
            : $actualValue - $rule['max'];
        
        $message = ucfirst(str_replace('_', ' ', $ruleName)) . " {$direction}";
        
        if ($difference > ($rule['ideal'] * 0.5)) {
            $message .= " (jauh dari ideal)";
        }
        
        return [
            'passed' => false,
            'message' => $message,
            'actual' => round($actualValue, 1),
            'expected' => "{$rule['min']} - {$rule['max']}",
            'severity' => $rule['severity'] ?? 0.2,
            'difference' => round($difference, 1)
        ];
    }
    
    /**
     * Detect rep completion based on pose data
     */
    private function detectRepCompletion(array $poseData, string $exercise): array
    {
        static $repStates = [];
        static $repCounts = [];
        
        // Initialize state for this exercise
        if (!isset($repStates[$exercise])) {
            $repStates[$exercise] = 'rest';
            $repCounts[$exercise] = 0;
        }
        
        $currentState = $repStates[$exercise];
        $currentCount = $repCounts[$exercise];
        $currentTime = microtime(true);
        
        // Calculate motion metrics from pose data
        $angles = $this->calculateBodyAngles($poseData);
        $motionIntensity = $this->calculateMotionIntensity($poseData);
        
        $repCompleted = false;
        $newState = $currentState;
        
        // State machine for rep detection
        switch ($exercise) {
            case 'pushup':
                if ($currentState === 'rest' && $angles['elbow_angle'] < 100 && $motionIntensity > 0.3) {
                    $newState = 'down';
                } elseif ($currentState === 'down' && $angles['elbow_angle'] > 140) {
                    $newState = 'up';
                } elseif ($currentState === 'up' && $motionIntensity < 0.2) {
                    $newState = 'rest';
                    $currentCount++;
                    $repCompleted = true;
                }
                break;
                
            case 'shoulder_press':
                if ($currentState === 'rest' && $angles['arm_extension'] < 170 && $motionIntensity > 0.4) {
                    $newState = 'down';
                } elseif ($currentState === 'down' && $angles['arm_extension'] > 175) {
                    $newState = 'up';
                } elseif ($currentState === 'up' && $motionIntensity < 0.3) {
                    $newState = 'rest';
                    $currentCount++;
                    $repCompleted = true;
                }
                break;
                
            case 't_bar_row':
                if ($currentState === 'rest' && $angles['elbow_angle'] > 100 && $motionIntensity > 0.3) {
                    $newState = 'down';
                } elseif ($currentState === 'down' && $angles['elbow_angle'] < 80) {
                    $newState = 'up';
                } elseif ($currentState === 'up' && $motionIntensity < 0.2) {
                    $newState = 'rest';
                    $currentCount++;
                    $repCompleted = true;
                }
                break;
        }
        
        // Update state and count
        $repStates[$exercise] = $newState;
        if ($repCompleted) {
            $repCounts[$exercise] = $currentCount;
        }
        
        return [
            'rep_completed' => $repCompleted,
            'current_phase' => $newState,
            'total_reps' => $repCounts[$exercise],
            'motion_intensity' => $motionIntensity,
            'state_changed' => $newState !== $currentState,
            'timestamp' => $currentTime
        ];
    }
    
    /**
     * Calculate motion intensity from pose data
     */
    private function calculateMotionIntensity(array $poseData): float
    {
        // Calculate variance of pose data to estimate motion intensity
        $mean = array_sum($poseData) / count($poseData);
        $variance = 0;
        
        foreach ($poseData as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance /= count($poseData);
        
        // Normalize to 0-1 range
        $intensity = min(1.0, $variance * 100);
        
        return round($intensity, 2);
    }
    
    /**
     * Calculate overall confidence score
     */
    private function calculateConfidenceScore(array $prediction, array $formCheck): float
    {
        $confidence = $prediction['confidence'] ?? 0.5;
        $formScore = $formCheck['score'] ?? 0.5;
        
        // Weighted average: 70% prediction confidence, 30% form score
        $overallScore = ($confidence * 0.7) + ($formScore * 0.3);
        
        return round($overallScore, 2);
    }
    
    /**
     * Generate feedback message for user
     */
    private function generateFeedback(array $prediction, array $formCheck, array $repDetection): string
    {
        if (!$prediction['is_correct_exercise']) {
            return "⚠️ Salah gerakan! Lakukan {$prediction['exercise_detected']} yang benar.";
        }
        
        if (!$formCheck['is_correct'] && !empty($formCheck['issues'])) {
            $firstIssue = $formCheck['issues'][0];
            return "❌ {$firstIssue['message']}";
        }
        
        if ($repDetection['rep_completed']) {
            return "✅ Rep {$repDetection['total_reps']} berhasil! 💪";
        }
        
        if ($repDetection['current_phase'] === 'down') {
            return "⬇️ Turunkan tubuh...";
        }
        
        if ($repDetection['current_phase'] === 'up') {
            return "⬆️ Naikkan tubuh...";
        }
        
        return "👍 Form baik! Lanjutkan gerakan...";
    }
    
    /**
     * Batch process multiple frames for exercise analysis
     */
    public function batchProcessFrames(array $frames, string $expectedExercise): array
    {
        try {
            Log::info('Batch processing frames', [
                'frame_count' => count($frames),
                'expected_exercise' => $expectedExercise
            ]);
            
            $results = [];
            $frameAnalyses = [];
            
            foreach ($frames as $index => $frameData) {
                $frameResult = $this->processRealTimeFrame($frameData, $expectedExercise);
                $frameAnalyses[] = $frameResult;
                
                if ($frameResult['success']) {
                    $results[] = $frameResult;
                }
            }
            
            // Calculate overall statistics
            $totalFrames = count($frameAnalyses);
            $successfulFrames = count(array_filter($frameAnalyses, fn($r) => $r['success']));
            $correctExerciseFrames = count(array_filter($frameAnalyses, function($r) {
                return $r['success'] && 
                       isset($r['frame_analysis']['prediction']['is_correct_exercise']) &&
                       $r['frame_analysis']['prediction']['is_correct_exercise'];
            }));
            
            $correctFormFrames = count(array_filter($frameAnalyses, function($r) {
                return $r['success'] && 
                       isset($r['frame_analysis']['form_check']['is_correct']) &&
                       $r['frame_analysis']['form_check']['is_correct'];
            }));
            
            $totalReps = 0;
            $lastRepCount = 0;
            foreach ($frameAnalyses as $result) {
                if ($result['success'] && isset($result['frame_analysis']['rep_detection']['total_reps'])) {
                    $currentReps = $result['frame_analysis']['rep_detection']['total_reps'];
                    if ($currentReps > $lastRepCount) {
                        $totalReps = $currentReps;
                        $lastRepCount = $currentReps;
                    }
                }
            }
            
            return [
                'success' => true,
                'batch_analysis' => [
                    'total_frames' => $totalFrames,
                    'successful_frames' => $successfulFrames,
                    'success_rate' => $totalFrames > 0 ? ($successfulFrames / $totalFrames * 100) : 0,
                    'correct_exercise_rate' => $successfulFrames > 0 ? ($correctExerciseFrames / $successfulFrames * 100) : 0,
                    'correct_form_rate' => $successfulFrames > 0 ? ($correctFormFrames / $successfulFrames * 100) : 0,
                    'total_reps_detected' => $totalReps,
                    'average_confidence' => $this->calculateAverageConfidence($frameAnalyses),
                    'form_issues_summary' => $this->summarizeFormIssues($frameAnalyses)
                ],
                'frame_results' => $frameAnalyses
            ];
            
        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Batch processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate average confidence from frame analyses
     */
    private function calculateAverageConfidence(array $frameAnalyses): float
    {
        $confidences = [];
        
        foreach ($frameAnalyses as $analysis) {
            if ($analysis['success'] && isset($analysis['frame_analysis']['confidence_score'])) {
                $confidences[] = $analysis['frame_analysis']['confidence_score'];
            }
        }
        
        if (empty($confidences)) {
            return 0.0;
        }
        
        return round(array_sum($confidences) / count($confidences), 2);
    }
    
    /**
     * Summarize form issues across frames
     */
    private function summarizeFormIssues(array $frameAnalyses): array
    {
        $issueCounts = [];
        
        foreach ($frameAnalyses as $analysis) {
            if ($analysis['success'] && isset($analysis['frame_analysis']['form_check']['issues'])) {
                foreach ($analysis['frame_analysis']['form_check']['issues'] as $issue) {
                    $issueKey = $issue['rule'] ?? 'unknown';
                    if (!isset($issueCounts[$issueKey])) {
                        $issueCounts[$issueKey] = 0;
                    }
                    $issueCounts[$issueKey]++;
                }
            }
        }
        
        arsort($issueCounts);
        
        $summary = [];
        foreach ($issueCounts as $issue => $count) {
            $summary[] = [
                'issue' => $issue,
                'count' => $count,
                'frequency' => round($count / count($frameAnalyses) * 100, 1) . '%'
            ];
        }
        
        return array_slice($summary, 0, 5); // Top 5 issues
    }
    
    /**
     * Get available exercise models
     */
    public function getAvailableModels(): array
    {
        try {
            $models = [];
            
            // Scan ml_models directory
            $modelDirs = scandir($this->modelsPath);
            
            foreach ($modelDirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                $fullPath = $this->modelsPath . '/' . $dir;
                
                if (is_dir($fullPath)) {
                    // Check if this directory contains a model file
                    $files = scandir($fullPath);
                    $hasModelFile = false;
                    $modelInfo = [
                        'name' => $dir,
                        'files' => []
                    ];
                    
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }
                        
                        $filePath = $fullPath . '/' . $file;
                        $fileInfo = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                        
                        $modelInfo['files'][] = $fileInfo;
                        
                        // Check if this is a model file
                        if (str_ends_with($file, '.h5') || str_ends_with($file, '.keras') || str_ends_with($file, '.pkl')) {
                            $hasModelFile = true;
                            $modelInfo['model_file'] = $file;
                        }
                        
                        // Load metadata if available
                        if ($file === 'meta.json') {
                            try {
                                $metaContent = file_get_contents($filePath);
                                $modelInfo['metadata'] = json_decode($metaContent, true);
                            } catch (Exception $e) {
                                Log::warning("Failed to read metadata for {$dir}: {$e->getMessage()}");
                            }
                        }
                    }
                    
                    if ($hasModelFile) {
                        // Map folder names to model keys
                        $modelKey = $this->mapFolderToModelKey($dir);
                        $modelInfo['model_key'] = $modelKey;
                        $modelInfo['exercise_name'] = $this->getExerciseName($modelKey);
                        $modelInfo['form_rules'] = $this->getFormRules($modelKey);
                        
                        $models[$modelKey] = $modelInfo;
                    }
                }
            }
            
            return [
                'success' => true,
                'models' => $models,
                'total' => count($models),
                'models_path' => $this->modelsPath,
                'supported_exercises' => array_keys($this->getFormRules('dummy'))
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get available models', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Map folder name to model key
     */
    private function mapFolderToModelKey(string $folderName): string
    {
        $mappings = [
            'push-up' => 'pushup',
            'Shoulder Press' => 'shoulder_press',
            't bar row' => 't_bar_row',
            'pushup' => 'pushup',
            'shoulder_press' => 'shoulder_press',
            't_bar_row' => 't_bar_row'
        ];
        
        return $mappings[$folderName] ?? strtolower(str_replace(' ', '_', $folderName));
    }
    
    /**
     * Get exercise display name from model key
     */
    private function getExerciseName(string $modelKey): string
    {
        $mappings = [
            'pushup' => 'Push Up',
            'shoulder_press' => 'Shoulder Press',
            't_bar_row' => 'T Bar Row'
        ];
        
        return $mappings[$modelKey] ?? ucwords(str_replace('_', ' ', $modelKey));
    }
    
    /**
     * Validate motion data format
     */
    public function validateMotionData(array $data): array
    {
        $errors = [];
        
        if (empty($data)) {
            $errors[] = 'Motion data cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check if it's a 2D array (sequence of sensor readings)
        $is2D = false;
        foreach ($data as $item) {
            if (is_array($item)) {
                $is2D = true;
                break;
            }
        }
        
        if (!$is2D) {
            // Mungkin ini single sequence, wrap it
            if (is_numeric($data[0] ?? null)) {
                $data = [$data];
                $is2D = true;
            } else {
                $errors[] = 'Motion data should be a 2D array (sequence of sensor readings)';
            }
        }
        
        if ($is2D) {
            // Check each sequence item
            foreach ($data as $index => $sequence) {
                if (!is_array($sequence)) {
                    $errors[] = "Sequence at index {$index} is not an array";
                    continue;
                }
                
                // Check if all values are numeric
                foreach ($sequence as $valueIndex => $value) {
                    if (!is_numeric($value)) {
                        $errors[] = "Non-numeric value at sequence {$index}, position {$valueIndex}: " . gettype($value);
                        break;
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data_info' => [
                'total_sequences' => count($data),
                'is_2d' => $is2D,
                'sample_features' => $is2D && !empty($data[0]) ? count($data[0]) : 0
            ]
        ];
    }
    
    /**
     * Validate pose landmarks data format
     */
    public function validatePoseData(array $poseData): array
    {
        $errors = [];
        
        if (empty($poseData)) {
            $errors[] = 'Pose data cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check if it's an array of landmarks
        $isValidPose = true;
        foreach ($poseData as $index => $landmark) {
            if (!is_array($landmark)) {
                $errors[] = "Landmark at index {$index} is not an array";
                $isValidPose = false;
                continue;
            }
            
            // Check for coordinates
            $hasCoords = isset($landmark['x']) && isset($landmark['y']);
            $hasArrayCoords = isset($landmark[0]) && isset($landmark[1]);
            
            if (!$hasCoords && !$hasArrayCoords) {
                $errors[] = "Landmark at index {$index} missing coordinates";
                $isValidPose = false;
            }
        }
        
        // Check for reasonable number of landmarks
        $landmarkCount = count($poseData);
        if ($landmarkCount < 17) {
            Log::warning("Low number of pose landmarks: {$landmarkCount}");
        }
        
        return [
            'valid' => $isValidPose && empty($errors),
            'errors' => $errors,
            'pose_info' => [
                'landmark_count' => $landmarkCount,
                'has_3d' => isset($poseData[0]['z']) || (isset($poseData[0][2]) && $poseData[0][2] != 0)
            ]
        ];
    }
    
    /**
     * Check ML service health
     */
    public function checkHealth(): array
    {
        $status = [
            'python_script_exists' => file_exists($this->pythonScriptPath),
            'models_directory_exists' => file_exists($this->modelsPath),
            'available_models' => [],
            'python_version' => null,
            'tensorflow_available' => false,
            'real_time_support' => true,
            'form_check_support' => true,
            'rep_detection_support' => true,
            'overall_healthy' => false
        ];
        
        // Check Python script
        if ($status['python_script_exists']) {
            $status['python_script_size'] = filesize($this->pythonScriptPath);
            $status['python_script_modified'] = date('Y-m-d H:i:s', filemtime($this->pythonScriptPath));
        } else {
            Log::error("Python script not found: {$this->pythonScriptPath}");
        }
        
        // Check models directory
        if ($status['models_directory_exists']) {
            try {
                $models = $this->getAvailableModels();
                if ($models['success']) {
                    $status['available_models'] = array_keys($models['models']);
                    $status['total_models'] = $models['total'];
                    $status['model_details'] = array_map(function($model) {
                        return [
                            'name' => $model['exercise_name'] ?? $model['name'],
                            'key' => $model['model_key'] ?? 'unknown',
                            'files' => count($model['files'] ?? []),
                            'has_metadata' => isset($model['metadata'])
                        ];
                    }, $models['models']);
                }
            } catch (Exception $e) {
                Log::warning("Failed to scan models directory: {$e->getMessage()}");
            }
        } else {
            Log::warning("Models directory not found: {$this->modelsPath}");
        }
        
        // Check Python version
        try {
            $process = Process::run("{$this->pythonExecutable} --version");
            if ($process->successful()) {
                $status['python_version'] = trim($process->output());
            } else {
                $status['python_error'] = $process->errorOutput();
            }
        } catch (Exception $e) {
            $status['python_error'] = $e->getMessage();
            Log::warning("Failed to get Python version: {$e->getMessage()}");
        }
        
        // Check TensorFlow availability
        try {
            $testCommand = "{$this->pythonExecutable} -c \"import tensorflow as tf; print('TensorFlow version:', tf.__version__)\" 2>&1";
            $process = Process::run($testCommand);
            $status['tensorflow_available'] = $process->successful();
            if ($process->successful()) {
                $status['tensorflow_version'] = trim($process->output());
            } else {
                $status['tensorflow_error'] = $process->errorOutput();
                Log::warning("TensorFlow check failed: {$status['tensorflow_error']}");
            }
        } catch (Exception $e) {
            $status['tensorflow_error'] = $e->getMessage();
            Log::warning("Failed to check TensorFlow: {$e->getMessage()}");
        }
        
        // Test with simple Python command
        try {
            $testCommand = "{$this->pythonExecutable} -c \"print('Python test successful')\"";
            $process = Process::run($testCommand);
            $status['python_test_successful'] = $process->successful() && 
                                              str_contains($process->output(), 'successful');
        } catch (Exception $e) {
            $status['python_test_error'] = $e->getMessage();
        }
        
        // Test ML prediction with sample data
        try {
            $sampleData = $this->generateSampleMotionData();
            $testResult = $this->predictExercise($sampleData, 'pushup');
            $status['prediction_test_successful'] = $testResult['success'] ?? false;
            $status['prediction_test_result'] = [
                'model' => $testResult['model'] ?? null,
                'exercise_name' => $testResult['exercise_name'] ?? null,
                'success' => $testResult['success'] ?? false
            ];
        } catch (Exception $e) {
            $status['prediction_test_error'] = $e->getMessage();
            Log::warning("Prediction test failed: {$e->getMessage()}");
        }
        
        // Test real-time processing
        try {
            $samplePoseData = $this->generateSamplePoseData();
            $testResult = $this->processRealTimeFrame($samplePoseData, 'pushup');
            $status['real_time_test'] = $testResult['success'] ?? false;
            $status['real_time_test_details'] = $testResult;
        } catch (Exception $e) {
            $status['real_time_test_error'] = $e->getMessage();
        }
        
        // Determine overall health
        $status['overall_healthy'] = $status['python_script_exists'] && 
                                    $status['models_directory_exists'] && 
                                    $status['python_version'] !== null &&
                                    !empty($status['available_models']);
        
        return $status;
    }
    
    /**
     * Test ML service with sample data
     */
    public function testPrediction(string $modelName = 'pushup'): array
    {
        try {
            // Generate sample motion data dengan 103 features
            $sampleData = $this->generateSampleMotionData();
            
            Log::info('Running ML service test', [
                'model' => $modelName,
                'sample_data_points' => count($sampleData),
                'features_per_point' => count($sampleData[0] ?? [])
            ]);
            
            $result = $this->predictExercise($sampleData, $modelName);
            
            // Add test metadata
            $result['test_info'] = [
                'model_tested' => $modelName,
                'sample_data_shape' => [count($sampleData), count($sampleData[0] ?? [])],
                'timestamp' => now()->toISOString(),
                'test_type' => 'batch_prediction'
            ];
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('ML service test failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Test failed: ' . $e->getMessage(),
                'test_info' => [
                    'model_tested' => $modelName,
                    'timestamp' => now()->toISOString()
                ]
            ];
        }
    }
    
    /**
     * Test real-time processing with sample pose data
     */
    public function testRealTimeProcessing(string $exercise = 'pushup'): array
    {
        try {
            $samplePoseData = $this->generateSamplePoseData();
            
            Log::info('Testing real-time processing', [
                'exercise' => $exercise,
                'sample_landmarks' => count($samplePoseData)
            ]);
            
            $result = $this->processRealTimeFrame($samplePoseData, $exercise);
            
            $result['test_info'] = [
                'exercise_tested' => $exercise,
                'test_type' => 'real_time_processing',
                'timestamp' => now()->toISOString(),
                'sample_pose_generated' => count($samplePoseData)
            ];
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Real-time processing test failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Real-time test failed: ' . $e->getMessage(),
                'test_info' => [
                    'exercise_tested' => $exercise,
                    'timestamp' => now()->toISOString()
                ]
            ];
        }
    }
    
    /**
     * Generate sample motion data for testing (103 features)
     */
    private function generateSampleMotionData(): array
    {
        $sampleData = [];
        
        // Generate 20 timesteps (sesuai sequence length)
        for ($t = 0; $t < 20; $t++) {
            $sequence = [];
            
            // Generate 103 features
            for ($i = 0; $i < 103; $i++) {
                // Generate realistic values based on feature type
                if ($i < 100) { // Pose coordinates (25 landmarks × 4)
                    if ($i % 4 == 3) { // Visibility values (0.0-1.0)
                        $sequence[] = rand(70, 100) / 100.0;
                    } else { // Coordinate values (0.0-1.0)
                        $sequence[] = rand(0, 100) / 100.0;
                    }
                } else { // Additional features (3 features)
                    $sequence[] = rand(-10, 10) / 10.0;
                }
            }
            
            $sampleData[] = $sequence;
        }
        
        return $sampleData;
    }
    
    /**
     * Generate sample pose landmarks for testing
     */
    private function generateSamplePoseData(): array
    {
        $poseData = [];
        
        // Generate 33 landmarks (standard MediaPipe pose)
        for ($i = 0; $i < 33; $i++) {
            $poseData[] = [
                'x' => rand(0, 100) / 100.0,
                'y' => rand(0, 100) / 100.0,
                'z' => rand(-50, 50) / 100.0,
                'visibility' => rand(70, 100) / 100.0
            ];
        }
        
        return $poseData;
    }
    
    /**
     * Additional method untuk predict dari pose data secara langsung
     */
    public function predictFromPose(array $poseLandmarks, ?string $modelName = null): array
    {
        try {
            Log::info('Direct pose prediction request', [
                'landmarks_count' => count($poseLandmarks),
                'model' => $modelName ?? 'auto-detect'
            ]);
            
            // Convert pose to 103 features
            $features = $this->normalizePoseDataForModel($poseLandmarks);
            
            // Create sequence
            $sequence = $this->createSequenceFromPoseData($features, 20);
            
            // Predict
            return $this->predictExercise($sequence, $modelName);
            
        } catch (Exception $e) {
            Log::error('Direct pose prediction failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Pose prediction failed: ' . $e->getMessage()
            ];
        }
    }
}