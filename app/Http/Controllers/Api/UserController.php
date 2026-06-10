<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Ticket; // Sesuaikan jika nama modelmu Transaction / Order
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Lihat Profil
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('role') // Load relasi role
        ]);
    }

    // Checkout / Beli Tiket
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $event = Event::find($request->event_id);
        $totalPrice = $event->price * $request->quantity;

        // Logic simpan ke tabel tickets / transactions
        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'status' => 'unpaid', // Default status
            // 'ticket_code' => uniqid('TIX-'), // Generate kode unik jika perlu
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout berhasil, silakan lakukan pembayaran.',
            'data' => $ticket
        ], 201);
    }

    // Lihat Daftar Tiket Saya
    public function tickets(Request $request)
    {
        // Ambil tiket milik user beserta detail event-nya
        $tickets = Ticket::with('event')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $tickets]);
    }

    // Lihat Detail Satu Tiket
    public function showTicket(Request $request, $id)
    {
        $ticket = Ticket::with('event')->where('user_id', $request->user()->id)->find($id);

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Tiket tidak ditemukan.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $ticket]);
    }

    // Upload Bukti Pembayaran
    public function uploadProof(Request $request, $id)
    {
        $ticket = Ticket::where('user_id', $request->user()->id)->find($id);

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Tiket tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|string', // Sementara string (misal base64 atau path dari mobile)
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $ticket->update([
            'payment_proof' => $request->payment_proof,
            'status' => 'pending', // Menunggu verifikasi organizer
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi.'
        ]);
    }
}
