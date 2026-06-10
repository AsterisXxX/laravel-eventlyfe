<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController; // Pastikan import controller baru ini
use Illuminate\Support\Facades\Route;

// ==================== ENDPOINT PUBLIK ====================
// (Bisa di-hit oleh user umum / guest tanpa perlu login)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/events', [EventController::class, 'index']);      // Ambil semua event (fetchEvents di Flutter)
Route::get('/events/{id}', [EventController::class, 'show']);   // Ambil detail satu event


// ==================== ENDPOINT PROTECTED ====================
// (Wajib membawa Bearer Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route khusus kelola event oleh Organizer / Creator
    // Jika kamu punya middleware 'role:organizer', silakan selipkan di sini
    Route::prefix('organizer')->group(function () {
        Route::post('/events', [EventController::class, 'store']);       // Tambah event baru
        Route::put('/events/{id}', [EventController::class, 'update']);   // Edit/Update event
        Route::delete('/events/{id}', [EventController::class, 'destroy']); // Hapus event
    });
});
