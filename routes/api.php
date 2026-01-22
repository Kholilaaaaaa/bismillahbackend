<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\FeedBackController;
use App\Http\Controllers\Api\DetailWorkoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [AuthController::class, 'validateToken']);
Route::get('/camera', function () {
    return view('camera');
})->name('camera.view');
  // Real-time workout detection routes
    Route::prefix('detailworkout')->group(function () {
    // Exercise options
    Route::get('/detailworkout/exercise-options', [DetailWorkoutController::class, 'getExerciseOptions']);
    
    // Exercise session management
    Route::post('/detailworkout/start-exercise', [DetailWorkoutController::class, 'startExerciseDetection']);
    Route::post('/detailworkout/detect-realtime', [DetailWorkoutController::class, 'detectRealTime']);
    Route::get('/detailworkout/exercise-session/{session_id}', [DetailWorkoutController::class, 'getExerciseSessionStatus']);
    Route::post('/detailworkout/complete-exercise', [DetailWorkoutController::class, 'completeExercise']);
    Route::post('/detailworkout/reset-counter', [DetailWorkoutController::class, 'resetCounter']);
    
    // ML Service health check
    Route::get('/detailworkout/ml-health', [DetailWorkoutController::class, 'checkMlHealth']);
    });

// Protected routes - require authentication
Route::middleware('auth.token')->group(function () {

    // Foodplan routes
    Route::get('/foods', [FoodController::class, 'getFoods']);

    // Jadwal routes
    Route::get('/jadwal/today', [JadwalController::class, 'getTodaySchedules']);

    // Workout routes
    Route::get('/workout/today', [WorkoutController::class, 'getTodayWorkouts']);

    // Auth + Profile routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);

    // Feedback routes
    Route::post('/feedback', [FeedBackController::class, 'store']);
    Route::post('/feedback/update', [FeedBackController::class, 'update']);
    Route::delete('/feedback', [FeedBackController::class, 'destroy']);
    Route::get('/feedback/my', [FeedBackController::class, 'myFeedback']);

    Route::prefix('chatbot')->group(function () {
        Route::post('/ask', [ChatbotController::class, 'ask']);
        Route::get('/knowledge', [ChatbotController::class, 'knowledgeBase']);
        Route::post('/knowledge', [ChatbotController::class, 'addKnowledge']);
        Route::get('/test-db', function() {
            return response()->json([
                'status' => 'success',
                'message' => 'Chatbot API is working'
            ]);
        });
    });
});