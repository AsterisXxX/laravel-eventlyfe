<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Ticket; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Lihat Profil
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('role')
        ]);
    }

    // Checkout / Beli Tiket
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1|max:5', // Batasan sama seperti web
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $event = Event::find($request->event_id);

        // 1. Cek Kuota
        if ($event->quota < $request->quantity) {
            return response()->json(['status' => 'error', 'message' => 'Maaf, kuota tiket tidak mencukupi.'], 400);
        }

        $createdTickets = [];

        // 2. Simpan Tiket ke Database (Looping berdasarkan quantity)
        for ($i = 0; $i < $request->quantity; $i++) {
            $createdTickets[] = Ticket::create([
                'user_id'       => $request->user()->id,
                'event_id'      => $event->id,
                'ticket_code'   => 'TKT-' . strtoupper(Str::random(10)),
                'price'         => $event->price,
                'status'        => 'unpaid', // Status awal sebelum upload bukti
                'is_scanned'    => false,
            ]);
        }

        // 3. Update Kuota Event
        $event->decrement('quota', $request->quantity);

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout berhasil, silakan unggah bukti pembayaran.',
            'data' => $createdTickets
        ], 201);
    }

    // Lihat Daftar Tiket Saya
    public function tickets(Request $request)
    {
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

        // Validasi Upload File Gambar yang sesungguhnya
        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('payment_proof')) {
            // Hapus Bukti Lama (jika re-upload)
            if ($ticket->payment_proof) {
                $oldPath = public_path('images/proofs/' . $ticket->payment_proof);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            // Proses File Baru
            $image = $request->file('payment_proof');
            $filename = 'proof_' . $ticket->id . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/proofs'), $filename);

            // Update database
            $ticket->update([
                'payment_proof' => $filename,
                'status' => 'pending', // Menunggu verifikasi organizer
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi organizer.',
                'data' => $ticket
            ], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'File tidak ditemukan.'], 400);
    }
}