<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Auth routes — public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/metrics', [TicketController::class, 'metrics']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    Route::post('/tickets/{id}/assign', [TicketController::class, 'assign']);
    Route::get('/tickets/{id}/activity', [TicketController::class, 'activity']);

    // Comments
    Route::get('/tickets/{ticket_id}/comments', [CommentController::class, 'index']);
    Route::post('/tickets/{ticket_id}/comments', [CommentController::class, 'store']);
});
