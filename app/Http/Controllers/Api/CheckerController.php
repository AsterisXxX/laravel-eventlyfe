<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CheckerController extends Controller
{
    // ==============================================================
    // BAGIAN ORGANIZER: Mengelola Akun Checker (apiResource)
    // ==============================================================

    public function index(Request $request)
    {
        // Ambil data user yang memiliki role checker (bisa di-filter berdasarkan organizer_id pembuatnya)
        $role = Role::where('slug', 'checker')->first();
        $checkers = User::where('role_id', $role->id)->get();

        return response()->json(['status' => 'success', 'data' => $checkers]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        $role = Role::where('slug', 'checker')->first();

        $checker = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'full_name' => $request->username, // Default
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            // 'organizer_id' => $request->user()->id // Jika checker terikat ke organizer tertentu
        ]);

        return response()->json(['status' => 'success', 'message' => 'Akun Checker berhasil dibuat', 'data' => $checker], 201);
    }

    // Untuk show(), update(), destroy() bisa dilanjut sesuai standar CRUD biasa.


    // ==============================================================
    // BAGIAN CHECKER: Melakukan Scanning Tiket di Lapangan
    // ==============================================================

    public function dashboard(Request $request)
    {
        // Menampilkan event apa saja yang ditugaskan ke checker ini (opsional)
        return response()->json(['status' => 'success', 'message' => 'Selamat datang di area Scanner']);
    }

    // Proses Verifikasi/Scan Tiket (QR Code)
    public function verifyTicket(Request $request, $eventId)
    {
        $validator = Validator::make($request->all(), [
            'ticket_code' => 'required|string', // Kode yang didapat dari scan QR di Flutter
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        // Cari tiket berdasarkan event dan kode tiket
        $ticket = Ticket::where('event_id', $eventId)
            ->where('ticket_code', $request->ticket_code) // Asumsi nama kolomnya ticket_code
            ->first();

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Tiket palsu atau tidak ditemukan di event ini!'], 404);
        }

        if ($ticket->status !== 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Tiket ini belum lunas / belum di-approve!'], 403);
        }

        // Jika ada status check-in, update statusnya agar tidak bisa dipakai 2 kali
        // $ticket->update(['is_scanned' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tiket Valid! Silakan masuk.',
            'data' => [
                'attendee_name' => $ticket->user->full_name ?? 'Peserta',
                'quantity' => $ticket->quantity
            ]
        ]);
    }
}
