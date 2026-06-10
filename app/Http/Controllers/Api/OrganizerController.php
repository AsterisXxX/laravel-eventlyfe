<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Ticket;

class OrganizerController extends Controller
{
    // Dashboard / Statistik Ringkas
    public function dashboard(Request $request)
    {
        $organizerId = $request->user()->id; // Asumsi kolom di event adalah organizer_id

        $totalEvents = Event::where('organizer_id', $organizerId)->count();
        // $totalTicketsSold = Ticket::whereHas('event', function($q) use($organizerId) { ... })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_events' => $totalEvents,
                // 'total_revenue' => ...
            ]
        ]);
    }

    // Lihat Daftar Antrean Verifikasi (Tiket berstatus 'pending')
    public function verifications(Request $request)
    {
        $organizerId = $request->user()->id;

        // Ambil tiket yang butuh verifikasi untuk event milik organizer ini
        $pendingTickets = Ticket::with(['user', 'event'])
            ->where('status', 'pending')
            ->whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId); // Sesuaikan nama kolom jika berbeda
            })
            ->get();

        return response()->json(['status' => 'success', 'data' => $pendingTickets]);
    }

    // Approve Tiket
    public function approveTicket(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
        }

        $ticket->update(['status' => 'approved']); // Tiket sah

        return response()->json(['status' => 'success', 'message' => 'Pembayaran tiket berhasil disetujui.']);
    }

    // Reject Tiket
    public function rejectTicket(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
        }

        $ticket->update(['status' => 'rejected']); // Tiket tidak sah

        return response()->json(['status' => 'success', 'message' => 'Pembayaran tiket ditolak.']);
    }
}
