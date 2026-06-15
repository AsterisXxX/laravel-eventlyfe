<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrganizerController;
use App\Http\Controllers\Api\CheckerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CategoryController;

// ==================== ENDPOINT PUBLIK ====================
// Bisa diakses tanpa login
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);


// ==================== ENDPOINT PROTECTED ====================
// Wajib membawa Bearer Token Sanctum
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware(['role:user'])->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::post('/checkout', [UserController::class, 'checkout']);
        Route::get('/my-tickets', [UserController::class, 'tickets']);
        Route::get('/my-tickets/{id}', [UserController::class, 'showTicket']);
        Route::post('/my-tickets/upload/{id}', [UserController::class, 'uploadProof']);
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

        // 🔥 UBAH BARIS INI: Hapus /{eventId} agar murni menggunakan body JSON ticket_code
        Route::post('/verify', [CheckerController::class, 'verifyTicket']);
    });


    // --------------------------------------------------------
    // 4. AREA ADMIN
    // --------------------------------------------------------
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']); // Statistik global aplikasi
        // Bisa dilanjut endpoint lain seperti kelola user, banned user, verifikasi organizer, dll.
    });
});
