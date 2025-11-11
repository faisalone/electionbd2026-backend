<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\SeatController;
use App\Http\Controllers\Admin\SymbolController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\TimelineController;
use App\Http\Controllers\Admin\PollController;
use App\Http\Controllers\Admin\NewsController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin API routes for your application.
| All routes are automatically prefixed with /api/admin.
|
*/

// Public admin routes (authentication)
Route::post('auth/send-otp', [AuthController::class, 'sendOTP']);
Route::post('auth/verify-otp', [AuthController::class, 'verifyOTP']);

// Protected admin routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    
    // Division routes
    Route::apiResource('divisions', DivisionController::class);
    
    // District routes
    Route::apiResource('districts', DistrictController::class);
    
    // Party routes
    Route::apiResource('parties', PartyController::class);
    
    // Seat routes
    Route::apiResource('seats', SeatController::class);
    
    // Symbol routes
    Route::apiResource('symbols', SymbolController::class);
    
    // Candidate routes
    Route::apiResource('candidates', CandidateController::class);
    
    // Timeline routes
    Route::apiResource('timeline', TimelineController::class);
    
    // Poll routes
    Route::apiResource('polls', PollController::class);
    Route::get('polls/{id}/votes', [PollController::class, 'votes']);
    Route::post('polls/{id}/select-winner', [PollController::class, 'selectWinner']);
    Route::post('polls/{id}/end', [PollController::class, 'endPoll']);
    
    // News routes
    Route::apiResource('news', NewsController::class);
    Route::post('news/generate', [NewsController::class, 'generateByTopic']);
    Route::post('news/run-cronjob', [NewsController::class, 'runCronjob']);
    Route::post('news/{id}/approve', [NewsController::class, 'approve']);
    Route::post('news/{id}/reject', [NewsController::class, 'reject']);
});
