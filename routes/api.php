<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DivisionController;
use App\Http\Controllers\API\DistrictController;
use App\Http\Controllers\API\SeatController;
use App\Http\Controllers\API\CandidateController;
use App\Http\Controllers\API\PartyController;
use App\Http\Controllers\API\TimelineController;
use App\Http\Controllers\API\PollController;
use App\Http\Controllers\API\NewsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| All routes are automatically prefixed with /api.
|
*/

// Version 1 API Routes
Route::prefix('v1')->group(function () {
    
    // Election Data Routes (Read-only)
    Route::apiResource('divisions', DivisionController::class)->only(['index', 'show']);
    Route::apiResource('districts', DistrictController::class)->only(['index', 'show']);
    Route::apiResource('seats', SeatController::class)->only(['index', 'show']);
    Route::apiResource('candidates', CandidateController::class)->only(['index', 'show']);
    Route::apiResource('parties', PartyController::class)->only(['index', 'show']);
    
    // Timeline
    Route::get('timeline', [TimelineController::class, 'index']);
    
    // News Routes
    Route::get('news', [NewsController::class, 'index']);
    Route::get('news/{id}', [NewsController::class, 'show']);
    
    // Poll Routes
    Route::get('polls', [PollController::class, 'index']);
    Route::get('polls/{poll}', [PollController::class, 'show']);
    Route::post('polls', [PollController::class, 'store']);
    Route::post('polls/{poll}/vote', [PollController::class, 'vote']);
    
    // OTP Routes
    Route::post('otp/send', [PollController::class, 'sendOTP']);
    Route::post('otp/verify', [PollController::class, 'verifyOTP']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
});
