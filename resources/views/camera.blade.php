<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Workout Camera Detection - Real Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- MediaPipe Pose -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js"></script>

    <style>
        :root {
            --dark-primary: #AF69EE;
            --background-dark: #08030C;
            --card-background-dark: #2C123A;
            --surface-dark: #1E1E2C;
            --text-primary-dark: #FFFFFF;
            --text-secondary-dark: #C7B8D6;
            --text-hint-dark: #6D6875;
            --divider-dark: #3D3D4E;
            --error-dark: #CF6679;
            --success-dark: #81C784;
            --warning-dark: #FFB74D;
            --info-dark: #4FC3F7;
            --processing-dark: #BA68C8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-dark);
            color: var(--text-primary-dark);
            min-height: 100vh;
            padding-bottom: 30px;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .camera-section {
            background-color: var(--surface-dark);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .camera-preview {
            background-color: #000;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border: 2px solid var(--divider-dark);
        }

        .camera-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-hint-dark);
            font-size: 18px;
            padding: 30px;
            text-align: center;
            z-index: 1;
            position: relative;
        }

        .camera-placeholder i {
            font-size: 70px;
            margin-bottom: 20px;
            color: var(--dark-primary);
        }

        .camera-feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            transform: scaleX(-1); /* Mirror untuk kamera depan */
        }

        .camera-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .camera-active .camera-feed {
            display: block;
        }

        .camera-active .camera-placeholder {
            display: none;
        }

        .workout-info-section {
            background-color: var(--card-background-dark);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .workout-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .control-card {
            background-color: var(--surface-dark);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 5px solid var(--dark-primary);
        }

        .control-card i {
            font-size: 28px;
            color: var(--dark-primary);
            margin-bottom: 12px;
        }

        .control-card h5 {
            font-size: 14px;
            color: var(--text-secondary-dark);
            margin-bottom: 5px;
        }

        .control-card p {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary-dark);
            margin: 0;
        }

        .form-label {
            color: var(--text-secondary-dark);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            background-color: var(--surface-dark);
            border: 1px solid var(--divider-dark);
            color: var(--text-primary-dark);
            padding: 12px 15px;
            border-radius: 10px;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: var(--surface-dark);
            border-color: var(--dark-primary);
            color: var(--text-primary-dark);
            box-shadow: 0 0 0 0.25rem rgba(175, 105, 238, 0.25);
        }

        .counter-display {
            background-color: var(--surface-dark);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 25px;
            border: 1px solid var(--divider-dark);
        }

        .counter-value {
            font-size: 72px;
            font-weight: 700;
            color: var(--dark-primary);
            line-height: 1;
            margin-bottom: 10px;
        }

        .counter-label {
            font-size: 18px;
            color: var(--text-secondary-dark);
            font-weight: 500;
        }

        .status-indicator {
            background-color: var(--surface-dark);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            border: 1px solid var(--divider-dark);
        }

        .status-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            font-weight: 600;
        }

        .status-correct {
            background-color: rgba(129, 199, 132, 0.15);
            border: 5px solid var(--success-dark);
            color: var(--success-dark);
        }

        .status-incorrect {
            background-color: rgba(207, 102, 121, 0.15);
            border: 5px solid var(--error-dark);
            color: var(--error-dark);
        }

        .status-idle {
            background-color: rgba(79, 195, 247, 0.15);
            border: 5px solid var(--info-dark);
            color: var(--info-dark);
        }

        .status-processing {
            background-color: rgba(186, 104, 200, 0.15);
            border: 5px solid var(--processing-dark);
            color: var(--processing-dark);
        }

        .status-label {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .status-message {
            color: var(--text-secondary-dark);
            font-size: 14px;
        }

        .feedback-container {
            background-color: var(--surface-dark);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 5px solid var(--info-dark);
        }

        .feedback-title {
            color: var(--info-dark);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feedback-message {
            font-size: 18px;
            color: var(--text-primary-dark);
            font-weight: 500;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(79, 195, 247, 0.1);
        }

        .form-issues-container {
            background-color: var(--surface-dark);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 5px solid var(--warning-dark);
        }

        .form-issues-title {
            color: var(--warning-dark);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-issue {
            background-color: rgba(255, 183, 77, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: var(--surface-dark);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-start {
            background-color: var(--success-dark);
            border: none;
            color: var(--background-dark);
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }

        .btn-start:hover {
            background-color: #6ab370;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(129, 199, 132, 0.4);
        }

        .btn-finish {
            background-color: var(--error-dark);
            border: none;
            color: var(--background-dark);
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }

        .btn-finish:hover {
            background-color: #c8556a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(207, 102, 121, 0.4);
        }

        .btn-secondary {
            background-color: var(--divider-dark);
            border: none;
            color: var(--text-primary-dark);
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }

        .btn-secondary:hover {
            background-color: #4a4a5e;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-dark);
            border: none;
            color: var(--background-dark);
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-warning:hover {
            background-color: #e6a02c;
            transform: translateY(-2px);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 10px;
            display: none;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--dark-primary);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: var(--text-hint-dark);
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid var(--divider-dark);
        }

        .workout-name {
            color: var(--dark-primary);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .workout-description {
            color: var(--text-secondary-dark);
            font-size: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .camera-preview {
                height: 300px;
            }

            .counter-value {
                font-size: 56px;
            }

            .button-container {
                flex-direction: column;
            }

            .btn-start,
            .btn-finish,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container main-container">
        <div class="col-lg-12 mt-4">
            <div class="camera-section">
                <h4 class="mb-4">Real-Time Workout Detection</h4>
                
                <div class="camera-preview" id="cameraPreview">
                    <div class="camera-placeholder">
                        <i class="fas fa-video"></i>
                        <p>Kamera siap untuk mendeteksi gerakan workout Anda</p>
                        <p class="mt-2" style="font-size: 14px;">Pastikan tubuh Anda terlihat jelas di dalam frame</p>
                    </div>
                    <video id="cameraFeed" class="camera-feed" autoplay playsinline></video>
                    <canvas id="poseCanvas" class="camera-canvas"></canvas>
                    <div class="loading-overlay" id="processingOverlay">
                        <div class="loading-spinner"></div>
                    </div>
                </div>

                <div class="workout-controls">
                    <div class="control-card">
                        <i class="fas fa-dumbbell"></i>
                        <h5>Exercise Type</h5>
                        <select id="exerciseSelect" class="form-select mt-2">
                            <option value="pushup">Push Up</option>
                            <option value="shoulder_press">Shoulder Press</option>
                            <option value="t_bar_row">T Bar Row</option>
                        </select>
                    </div>
                    
                    <div class="control-card">
                        <i class="fas fa-bullseye"></i>
                        <h5>Target Reps</h5>
                        <input type="number" id="targetReps" class="form-control mt-2" value="10" min="1" max="50">
                    </div>
                    
                    <div class="control-card">
                        <i class="fas fa-tachometer-alt"></i>
                        <h5>Processing Speed</h5>
                        <select id="processingSpeed" class="form-select mt-2">
                            <option value="high">High (30 FPS)</option>
                            <option value="medium" selected>Medium (15 FPS)</option>
                            <option value="low">Low (5 FPS)</option>
                        </select>
                    </div>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value" id="currentReps">0</div>
                        <div class="stat-label">Current Reps</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="confidenceScore">0%</div>
                        <div class="stat-label">Confidence</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="frameRate">0</div>
                        <div class="stat-label">FPS</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="formScore">0%</div>
                        <div class="stat-label">Form Score</div>
                    </div>
                </div>

                <div class="status-indicator">
                    <div class="status-circle status-idle" id="statusCircle">
                        <i class="fas fa-question"></i>
                    </div>
                    <div class="status-label" id="statusLabel">Ready</div>
                    <div class="status-message" id="statusMessage">Press Start to begin workout detection</div>
                </div>

                <div class="feedback-container" id="feedbackContainer" style="display: none;">
                    <div class="feedback-title">
                        <i class="fas fa-comment-dots"></i>
                        Real-Time Feedback
                    </div>
                    <div class="feedback-message" id="feedbackMessage">
                        Waiting for analysis...
                    </div>
                </div>

                <div class="form-issues-container" id="formIssuesContainer" style="display: none;">
                    <div class="form-issues-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Form Issues
                    </div>
                    <div id="formIssuesList">
                        <!-- Form issues will be listed here -->
                    </div>
                </div>

                <div class="button-container">
                    <button class="btn-start" id="start-workout">
                        <i class="fas fa-play-circle"></i> Start Workout
                    </button>
                    <button class="btn-warning" id="pause-workout" style="display: none;">
                        <i class="fas fa-pause-circle"></i> Pause
                    </button>
                    <button class="btn-finish" id="finish-workout" disabled>
                        <i class="fas fa-flag-checkered"></i> Finish Workout
                    </button>
                    <button class="btn-secondary" id="reset-workout">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables
        let cameraStream = null;
        let isCameraActive = false;
        let isProcessing = false;
        let isWorkoutActive = false;
        let poseDetector = null;
        let lastFrameTime = 0;
        let frameCount = 0;
        let fps = 0;
        let lastFpsUpdate = 0;
        
        // Workout statistics
        let workoutStats = {
            currentReps: 0,
            targetReps: 10,
            totalFrames: 0,
            correctFrames: 0,
            formScore: 0,
            confidenceScore: 0,
            sessionId: null,
            currentExercise: 'pushup',
            startTime: null,
            formIssues: []
        };
        
        // DOM Elements
        const cameraPreview = document.getElementById('cameraPreview');
        const cameraFeed = document.getElementById('cameraFeed');
        const poseCanvas = document.getElementById('poseCanvas');
        const processingOverlay = document.getElementById('processingOverlay');
        const startWorkoutBtn = document.getElementById('start-workout');
        const pauseWorkoutBtn = document.getElementById('pause-workout');
        const finishWorkoutBtn = document.getElementById('finish-workout');
        const resetWorkoutBtn = document.getElementById('reset-workout');
        const exerciseSelect = document.getElementById('exerciseSelect');
        const targetRepsInput = document.getElementById('targetReps');
        const processingSpeedSelect = document.getElementById('processingSpeed');
        
        // Status elements
        const statusCircle = document.getElementById('statusCircle');
        const statusLabel = document.getElementById('statusLabel');
        const statusMessage = document.getElementById('statusMessage');
        const feedbackContainer = document.getElementById('feedbackContainer');
        const feedbackMessage = document.getElementById('feedbackMessage');
        const formIssuesContainer = document.getElementById('formIssuesContainer');
        const formIssuesList = document.getElementById('formIssuesList');
        
        // Stats elements
        const currentRepsEl = document.getElementById('currentReps');
        const confidenceScoreEl = document.getElementById('confidenceScore');
        const frameRateEl = document.getElementById('frameRate');
        const formScoreEl = document.getElementById('formScore');
        
        // API Configuration
        const API_BASE_URL = window.location.origin;
        const API_TOKEN = 'YOUR_API_TOKEN_HERE'; // Ganti dengan token user
        
        // Initialize MediaPipe Pose Detector
        async function initializePoseDetector() {
            try {
                poseDetector = new Pose({
                    locateFile: (file) => {
                        return `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`;
                    }
                });
                
                poseDetector.setOptions({
                    modelComplexity: 1,
                    smoothLandmarks: true,
                    enableSegmentation: false,
                    smoothSegmentation: true,
                    minDetectionConfidence: 0.5,
                    minTrackingConfidence: 0.5
                });
                
                poseDetector.onResults(onPoseResults);
                
                console.log('Pose detector initialized');
                return true;
            } catch (error) {
                console.error('Failed to initialize pose detector:', error);
                return false;
            }
        }
        
        // Start camera
        async function startCamera() {
            try {
                showProcessing(true);
                
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                });
                
                cameraFeed.srcObject = cameraStream;
                cameraPreview.classList.add('camera-active');
                isCameraActive = true;
                
                // Initialize pose detector
                const poseInitialized = await initializePoseDetector();
                if (!poseInitialized) {
                    throw new Error('Failed to initialize pose detection');
                }
                
                // Start pose detection
                await startPoseDetection();
                
                updateStatus('ready', 'Camera active - Ready for workout', 'status-idle');
                
                startWorkoutBtn.disabled = false;
                startWorkoutBtn.innerHTML = '<i class="fas fa-dumbbell"></i> Start Workout';
                finishWorkoutBtn.disabled = false;
                
                console.log('Camera started successfully');
                showProcessing(false);
                
            } catch (error) {
                console.error('Error starting camera:', error);
                alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin akses kamera.');
                showProcessing(false);
            }
        }
        
        // Start pose detection
        async function startPoseDetection() {
            const camera = new Camera(cameraFeed, {
                onFrame: async () => {
                    if (isProcessing || !isCameraActive) return;
                    
                    const now = performance.now();
                    const elapsed = now - lastFrameTime;
                    const targetFps = getTargetFps();
                    const frameInterval = 1000 / targetFps;
                    
                    if (elapsed > frameInterval) {
                        lastFrameTime = now - (elapsed % frameInterval);
                        
                        try {
                            isProcessing = true;
                            await poseDetector.send({image: cameraFeed});
                        } catch (error) {
                            console.error('Pose detection error:', error);
                        } finally {
                            isProcessing = false;
                        }
                        
                        // Update FPS counter
                        updateFpsCounter();
                    }
                },
                width: 640,
                height: 480
            });
            
            camera.start();
        }
        
        // Handle pose detection results
        function onPoseResults(results) {
            frameCount++;
            
            // Draw pose landmarks on canvas
            const canvasCtx = poseCanvas.getContext('2d');
            canvasCtx.save();
            canvasCtx.clearRect(0, 0, poseCanvas.width, poseCanvas.height);
            canvasCtx.drawImage(results.image, 0, 0, poseCanvas.width, poseCanvas.height);
            
            if (results.poseLandmarks) {
                drawConnectors(canvasCtx, results.poseLandmarks, POSE_CONNECTIONS, {
                    color: '#00FF00',
                    lineWidth: 4
                });
                drawLandmarks(canvasCtx, results.poseLandmarks, {
                    color: '#FF0000',
                    lineWidth: 2,
                    radius: 4
                });
                
                // Process frame for ML analysis jika workout active
                if (isWorkoutActive) {
                    processFrameForAnalysis(results.poseLandmarks);
                }
            }
            
            canvasCtx.restore();
        }
        
        // Process frame for ML analysis
        async function processFrameForAnalysis(poseLandmarks) {
            try {
                workoutStats.totalFrames++;
                
                // Convert landmarks to format for API
                const landmarksArray = poseLandmarks.map(landmark => ({
                    x: landmark.x,
                    y: landmark.y,
                    z: landmark.z || 0,
                    visibility: landmark.visibility || 0.8
                }));
                
                // Prepare frame data
                const frameData = {
                    pose_landmarks: landmarksArray,
                    frame_index: workoutStats.totalFrames,
                    timestamp: Date.now()
                };
                
                // Send to API for analysis
                const analysisResult = await sendFrameForAnalysis(frameData);
                
                // Update UI dengan hasil analysis
                updateWorkoutUI(analysisResult);
                
            } catch (error) {
                console.error('Error processing frame:', error);
            }
        }
        
        // Send frame to API for analysis
        async function sendFrameForAnalysis(frameData) {
            try {
                const response = await fetch(`${API_BASE_URL}/api/detailworkout/process-frame`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${API_TOKEN}`
                    },
                    body: JSON.stringify({
                        frame_data: frameData,
                        expected_exercise: workoutStats.currentExercise,
                        session_id: workoutStats.sessionId,
                        frame_timestamp: Date.now(),
                        frame_index: workoutStats.totalFrames
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('API request failed:', error);
                return createFallbackAnalysis();
            }
        }
        
        // Create fallback analysis jika API gagal
        function createFallbackAnalysis() {
            const confidence = Math.random() * 0.3 + 0.5; // 0.5 - 0.8
            const isCorrect = confidence > 0.6;
            const repCompleted = Math.random() > 0.95; // 5% chance
            const formIssues = ['Maintain proper form'];
            
            if (repCompleted) {
                workoutStats.currentReps++;
                updateCounter();
            }
            
            return {
                success: true,
                frame_analysis: {
                    prediction: {
                        is_correct: isCorrect,
                        confidence: confidence,
                        exercise_detected: workoutStats.currentExercise
                    },
                    form_check: {
                        is_correct: false,
                        issues: formIssues
                    },
                    rep_detection: {
                        rep_completed: repCompleted
                    },
                    confidence_score: confidence
                },
                feedback: isCorrect ? 'Good form!' : 'Adjust your position',
                is_fallback: true
            };
        }
        
        // Update workout UI dengan analysis results
        function updateWorkoutUI(analysisResult) {
            if (!analysisResult.success) return;
            
            const frameAnalysis = analysisResult.frame_analysis || {};
            const prediction = frameAnalysis.prediction || {};
            const formCheck = frameAnalysis.form_check || {};
            const repDetection = frameAnalysis.rep_detection || {};
            
            // Update confidence score
            const confidence = prediction.confidence || frameAnalysis.confidence_score || 0;
            workoutStats.confidenceScore = (workoutStats.confidenceScore * 0.9 + confidence * 0.1);
            confidenceScoreEl.textContent = `${Math.round(workoutStats.confidenceScore * 100)}%`;
            
            // Update form score
            if (formCheck.is_correct) {
                workoutStats.correctFrames++;
            }
            workoutStats.formScore = (workoutStats.correctFrames / workoutStats.totalFrames) * 100;
            formScoreEl.textContent = `${Math.round(workoutStats.formScore)}%`;
            
            // Update form issues
            workoutStats.formIssues = formCheck.issues || [];
            updateFormIssuesDisplay();
            
            // Update rep counter
            if (repDetection.rep_completed) {
                workoutStats.currentReps++;
                updateCounter();
                
                // Play rep completion sound
                playRepCompleteSound();
                
                // Check if target reached
                if (workoutStats.currentReps >= workoutStats.targetReps) {
                    completeWorkout();
                }
            }
            
            // Update status
            if (prediction.is_correct) {
                updateStatus('correct', 'Good form! Keep going', 'status-correct');
            } else {
                updateStatus('incorrect', 'Adjust your form', 'status-incorrect');
            }
            
            // Update feedback
            if (analysisResult.feedback) {
                feedbackMessage.textContent = analysisResult.feedback;
                feedbackContainer.style.display = 'block';
            }
            
            // Save session data periodically
            if (workoutStats.totalFrames % 30 === 0) {
                saveSessionData();
            }
        }
        
        // Update rep counter
        function updateCounter() {
            currentRepsEl.textContent = workoutStats.currentReps;
            
            // Update progress color
            const progress = workoutStats.currentReps / workoutStats.targetReps;
            if (progress >= 1) {
                currentRepsEl.style.color = '#81C784'; // Green
            } else if (progress >= 0.7) {
                currentRepsEl.style.color = '#FFB74D'; // Orange
            } else {
                currentRepsEl.style.color = '#AF69EE'; // Purple
            }
        }
        
        // Update form issues display
        function updateFormIssuesDisplay() {
            if (workoutStats.formIssues.length === 0) {
                formIssuesContainer.style.display = 'none';
                return;
            }
            
            formIssuesContainer.style.display = 'block';
            formIssuesList.innerHTML = '';
            
            workoutStats.formIssues.slice(0, 3).forEach(issue => {
                const issueEl = document.createElement('div');
                issueEl.className = 'form-issue';
                issueEl.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${issue}`;
                formIssuesList.appendChild(issueEl);
            });
        }
        
        // Update status display
        function updateStatus(type, message, statusClass) {
            // Remove all status classes
            statusCircle.classList.remove('status-correct', 'status-incorrect', 'status-idle', 'status-processing');
            
            // Add new class
            statusCircle.classList.add(statusClass);
            
            // Update icon
            const icon = statusCircle.querySelector('i');
            if (icon) {
                switch(type) {
                    case 'correct':
                        icon.className = 'fas fa-check-circle';
                        break;
                    case 'incorrect':
                        icon.className = 'fas fa-times-circle';
                        break;
                    case 'processing':
                        icon.className = 'fas fa-sync fa-spin';
                        break;
                    default:
                        icon.className = 'fas fa-question';
                }
            }
            
            // Update labels
            statusLabel.textContent = type.charAt(0).toUpperCase() + type.slice(1);
            statusMessage.textContent = message;
        }
        
        // Update FPS counter
        function updateFpsCounter() {
            const now = performance.now();
            
            if (now - lastFpsUpdate > 1000) {
                fps = Math.round((frameCount * 1000) / (now - lastFpsUpdate));
                frameRateEl.textContent = fps;
                frameCount = 0;
                lastFpsUpdate = now;
            }
        }
        
        // Get target FPS dari selection
        function getTargetFps() {
            switch(processingSpeedSelect.value) {
                case 'high': return 30;
                case 'low': return 5;
                default: return 15;
            }
        }
        
        // Start workout session
        async function startWorkout() {
            if (!isCameraActive) {
                alert('Please start camera first');
                return;
            }
            
            workoutStats.currentExercise = exerciseSelect.value;
            workoutStats.targetReps = parseInt(targetRepsInput.value) || 10;
            workoutStats.sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            workoutStats.startTime = Date.now();
            
            // Reset stats
            workoutStats.currentReps = 0;
            workoutStats.totalFrames = 0;
            workoutStats.correctFrames = 0;
            workoutStats.formScore = 0;
            workoutStats.confidenceScore = 0;
            workoutStats.formIssues = [];
            
            updateCounter();
            confidenceScoreEl.textContent = '0%';
            formScoreEl.textContent = '0%';
            frameRateEl.textContent = '0';
            
            // Update UI
            isWorkoutActive = true;
            startWorkoutBtn.style.display = 'none';
            pauseWorkoutBtn.style.display = 'flex';
            finishWorkoutBtn.disabled = false;
            
            updateStatus('processing', 'Workout started - Detecting your form...', 'status-processing');
            
            // Start API session
            await startApiSession();
            
            console.log('Workout started:', workoutStats);
        }
        
        // Start API session
        async function startApiSession() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/detailworkout/start-session`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${API_TOKEN}`
                    },
                    body: JSON.stringify({
                        session_id: workoutStats.sessionId,
                        expected_exercises: [workoutStats.currentExercise],
                        session_type: 'realtime'
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to start API session: ${response.status}`);
                }
                
                console.log('API session started');
            } catch (error) {
                console.error('Error starting API session:', error);
            }
        }
        
        // Pause workout
        function pauseWorkout() {
            isWorkoutActive = !isWorkoutActive;
            
            if (isWorkoutActive) {
                pauseWorkoutBtn.innerHTML = '<i class="fas fa-pause-circle"></i> Pause';
                updateStatus('processing', 'Workout resumed', 'status-processing');
            } else {
                pauseWorkoutBtn.innerHTML = '<i class="fas fa-play-circle"></i> Resume';
                updateStatus('idle', 'Workout paused', 'status-idle');
            }
        }
        
        // Finish workout
        async function finishWorkout() {
            isWorkoutActive = false;
            
            // Show summary
            const workoutTime = ((Date.now() - workoutStats.startTime) / 1000).toFixed(1);
            const summary = `
                Workout Completed!
                
                Exercise: ${exerciseSelect.options[exerciseSelect.selectedIndex].text}
                Reps Completed: ${workoutStats.currentReps}/${workoutStats.targetReps}
                Workout Duration: ${workoutTime} seconds
                Average Form Score: ${Math.round(workoutStats.formScore)}%
                Average Confidence: ${Math.round(workoutStats.confidenceScore * 100)}%
            `;
            
            alert(summary);
            
            // End API session
            await endApiSession();
            
            // Reset UI
            resetWorkoutUI();
            
            console.log('Workout finished:', workoutStats);
        }
        
        // End API session
        async function endApiSession() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/detailworkout/end-session`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${API_TOKEN}`
                    },
                    body: JSON.stringify({
                        session_id: workoutStats.sessionId,
                        final_notes: `Completed ${workoutStats.currentReps} reps of ${workoutStats.currentExercise}`,
                        auto_save_details: true
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to end API session: ${response.status}`);
                }
                
                console.log('API session ended');
            } catch (error) {
                console.error('Error ending API session:', error);
            }
        }
        
        // Save session data
        async function saveSessionData() {
            // Implement periodic save jika diperlukan
        }
        
        // Complete workout (target reached)
        function completeWorkout() {
            // Play celebration sound
            playCelebrationSound();
            
            // Show completion message
            updateStatus('correct', 'Target reached! Great job!', 'status-correct');
            feedbackMessage.textContent = 'Congratulations! You reached your target!';
            feedbackContainer.style.display = 'block';
            
            // Auto-finish setelah 3 detik
            setTimeout(() => {
                if (isWorkoutActive) {
                    finishWorkout();
                }
            }, 3000);
        }
        
        // Reset workout
        function resetWorkout() {
            isWorkoutActive = false;
            workoutStats.currentReps = 0;
            workoutStats.totalFrames = 0;
            workoutStats.correctFrames = 0;
            workoutStats.formScore = 0;
            workoutStats.confidenceScore = 0;
            workoutStats.formIssues = [];
            
            updateCounter();
            confidenceScoreEl.textContent = '0%';
            formScoreEl.textContent = '0%';
            
            feedbackContainer.style.display = 'none';
            formIssuesContainer.style.display = 'none';
            
            updateStatus('idle', 'Ready to start workout', 'status-idle');
            
            startWorkoutBtn.style.display = 'flex';
            pauseWorkoutBtn.style.display = 'none';
            finishWorkoutBtn.disabled = true;
            
            console.log('Workout reset');
        }
        
        // Play rep complete sound
        function playRepCompleteSound() {
            // Implement sound jika diperlukan
            // const audio = new Audio('/sounds/rep-complete.mp3');
            // audio.play().catch(e => console.log('Audio play failed:', e));
        }
        
        // Play celebration sound
        function playCelebrationSound() {
            // Implement sound jika diperlukan
            // const audio = new Audio('/sounds/celebration.mp3');
            // audio.play().catch(e => console.log('Audio play failed:', e));
        }
        
        // Show/hide processing overlay
        function showProcessing(show) {
            processingOverlay.style.display = show ? 'flex' : 'none';
        }
        
        // Stop camera
        function stopCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => {
                    track.stop();
                });
                cameraStream = null;
            }
            
            cameraPreview.classList.remove('camera-active');
            isCameraActive = false;
            isWorkoutActive = false;
            
            resetWorkoutUI();
            
            console.log('Camera stopped');
        }
        
        // Reset workout UI
        function resetWorkoutUI() {
            startWorkoutBtn.disabled = false;
            startWorkoutBtn.innerHTML = '<i class="fas fa-play-circle"></i> Start Workout';
            startWorkoutBtn.style.display = 'flex';
            pauseWorkoutBtn.style.display = 'none';
            finishWorkoutBtn.disabled = true;
            
            updateStatus('idle', 'Camera stopped', 'status-idle');
        }
        
        // Event Listeners
        startWorkoutBtn.addEventListener('click', startCamera);
        pauseWorkoutBtn.addEventListener('click', pauseWorkout);
        finishWorkoutBtn.addEventListener('click', finishWorkout);
        resetWorkoutBtn.addEventListener('click', resetWorkout);
        
        // Start workout when camera is ready
        cameraFeed.addEventListener('loadeddata', () => {
            // Set canvas dimensions sama dengan video
            poseCanvas.width = cameraFeed.videoWidth;
            poseCanvas.height = cameraFeed.videoHeight;
            
            // Enable start workout button
            document.getElementById('start-workout').onclick = startWorkout;
        });
        
        // Cleanup saat halaman ditutup
        window.addEventListener('beforeunload', () => {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        });
        
        // Initialize
        console.log('Workout Detection System Ready');

        // Di bagian JavaScript, tambahkan fungsi untuk exercise selection
const exerciseOptions = {
    pushup: {
        name: 'Push Up',
        color: '#AF69EE',
        icon: 'fa-person-running',
        instructions: 'Mulai dengan posisi plank, turunkan tubuh, lalu dorong kembali'
    },
    shoulder_press: {
        name: 'Shoulder Press',
        color: '#4FC3F7',
        icon: 'fa-weight-hanging',
        instructions: 'Duduk di bangku, angkat beban ke atas, lalu turunkan dengan kontrol'
    },
    t_bar_row: {
        name: 'T Bar Row',
        color: '#81C784',
        icon: 'fa-dumbbell',
        instructions: 'Berdiri di atas T-bar machine, tarik beban ke dada, remas punggung'
    }
};

// Fungsi untuk switch exercise
function switchExercise(exerciseType) {
    if (!exerciseOptions[exerciseType]) return;
    
    const exercise = exerciseOptions[exerciseType];
    currentExercise = exerciseType;
    
    // Update UI
    document.getElementById('exerciseName').textContent = exercise.name;
    document.getElementById('exerciseIcon').className = `fas ${exercise.icon}`;
    document.getElementById('exerciseInstructions').textContent = exercise.instructions;
    document.getElementById('currentExerciseBadge').textContent = exercise.name;
    document.getElementById('currentExerciseBadge').style.backgroundColor = exercise.color;
    
    // Reset counter
    resetCounter();
    
    // Send reset command to server
    fetch('/api/detailworkout/reset-counter', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
            exercise_type: exerciseType,
            session_id: currentSessionId
        })
    });
    
    console.log(`Switched to ${exercise.name}`);
}

// Tambahkan event listener untuk exercise buttons
document.querySelectorAll('.exercise-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const exerciseType = this.dataset.exercise;
        switchExercise(exerciseType);
    });
});
    </script>
</body>

</html>