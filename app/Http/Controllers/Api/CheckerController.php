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
    // BAGIAN ORGANIZER: Mengelola Akun Checker
    // ==============================================================

    public function index(Request $request)
    {
        $role = Role::where('slug', 'checker')->first();
        $checkers = User::where('role_id', $role->id)->get();

        return response()->json(['status' => 'success', 'data' => $checkers], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $role = Role::where('slug', 'checker')->first();

        $checker = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'full_name' => $request->username,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Akun Checker berhasil dibuat', 
            'data' => $checker
        ], 201);
    }


    // ==============================================================
    // BAGIAN CHECKER: Melakukan Scanning Tiket di Lapangan
    // ==============================================================

    public function dashboard(Request $request)
    {
        return response()->json([
            'status' => 'success', 
            'message' => 'Selamat datang di area Scanner'
        ], 200);
    }

    // Proses Verifikasi/Scan Tiket (QR Code)
    public function verifyTicket(Request $request)
    {
        // Cukup butuh ticket_code dari hasil scan QR
        $validator = Validator::make($request->all(), [
            'ticket_code' => 'required|string', 
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Cari tiket berdasarkan kode tiket yang di-scan
        $ticket = Ticket::with(['user', 'event'])->where('ticket_code', $request->ticket_code)->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Tiket palsu atau tidak ditemukan!'
            ], 404);
        }

        // 1. Cek apakah tiket sudah lunas
        if ($ticket->status !== 'paid') {
            return response()->json([
                'status' => 'error', 
                'message' => 'Tiket ini belum lunas atau belum diverifikasi oleh Organizer!'
            ], 403);
        }

        // 2. Cek apakah tiket sudah pernah di-scan sebelumnya (Double Scan Prevention)
        if ($ticket->is_scanned) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Tiket sudah digunakan / sudah check-in sebelumnya!'
            ], 400);
        }

        // 3. Jika aman, tandai tiket sebagai sudah digunakan
        $ticket->update(['is_scanned' => true]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Tiket Valid! Silakan masuk.',
            'data' => [
                'attendee_name' => $ticket->user->full_name ?? $ticket->user->username ?? 'Peserta',
                'event_name' => $ticket->event->name ?? 'Event',
                'ticket_code' => $ticket->ticket_code
            ]
        ], 200);
    }
}