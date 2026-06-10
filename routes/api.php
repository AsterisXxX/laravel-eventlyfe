<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrganizerController;
use App\Http\Controllers\Api\CheckerController;
use App\Http\Controllers\Api\AdminController;

// ==================== ENDPOINT PUBLIK ====================
// Bisa diakses tanpa login
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/events', [EventController::class, 'index']);      // Ambil semua event (Home/Discover)
Route::get('/events/{id}', [EventController::class, 'show']);  // Ambil detail satu event


// ==================== ENDPOINT PROTECTED ====================
// Wajib membawa Bearer Token Sanctum
Route::middleware(['auth:sanctum'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // --------------------------------------------------------
    // 1. AREA USER (PEMBELI)
    // --------------------------------------------------------
    Route::middleware(['role:user'])->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);

        // Transaksi & Tiket
        Route::post('/checkout', [UserController::class, 'checkout']);
        Route::get('/my-tickets', [UserController::class, 'tickets']);
        Route::get('/my-tickets/{id}', [UserController::class, 'showTicket']);
        Route::post('/my-tickets/upload/{id}', [UserController::class, 'uploadProof']); // Upload bukti bayar
        // Route::get('/my-tickets/{id}/print', [...]) -> Bisa ditangani di sisi mobile untuk generate PDF/QR
    });


    // --------------------------------------------------------
    // 2. AREA ORGANIZER (CREATOR)
    // --------------------------------------------------------
    Route::middleware(['role:organizer'])->prefix('organizer')->group(function () {
        Route::get('/dashboard', [OrganizerController::class, 'dashboard']); // Statistik organizer

        // Kelola Event
        Route::get('/events', [EventController::class, 'organizerEvents']);  // List event milik organizer
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
        Route::post('/events/{id}/assign', [EventController::class, 'assignCheckers']); // Assign checker ke event

        // Kelola Checker (Staff Scanner)
        Route::apiResource('checkers', CheckerController::class); // Otomatis membuat route index, store, show, update, destroy

        // Verifikasi Tiket Pembeli (Upload Bukti Bayar)
        Route::get('/verifications', [OrganizerController::class, 'verifications']); // List antrean verifikasi
        Route::post('/verifications/{id}/approve', [OrganizerController::class, 'approveTicket']);
        Route::post('/verifications/{id}/reject', [OrganizerController::class, 'rejectTicket']);
    });


    // --------------------------------------------------------
    // 3. AREA CHECKER (STAFF SCANNER)
    // --------------------------------------------------------
    Route::middleware(['role:checker'])->prefix('checker')->group(function () {
        Route::get('/dashboard', [CheckerController::class, 'dashboard']); // List event yang ditugaskan ke checker ini

        // Endpoint utama untuk mobile scanner (QR Code dsb)
        Route::post('/verify/{eventId}', [CheckerController::class, 'verifyTicket']);
    });


    // --------------------------------------------------------
    // 4. AREA ADMIN
    // --------------------------------------------------------
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']); // Statistik global aplikasi
        // Bisa dilanjut endpoint lain seperti kelola user, banned user, verifikasi organizer, dll.
    });
});
