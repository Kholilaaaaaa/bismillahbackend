<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\FeedBackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [AuthController::class, 'validateToken']);

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
});