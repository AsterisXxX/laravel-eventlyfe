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
        $organizerId = $request->user()->id;

        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        // Hitung tiket lunas
        $totalTicketsPaid = Ticket::whereHas('event', function($q) use($organizerId) {
            $q->where('organizer_id', $organizerId);
        })->where('status', 'paid')->count();

        // Hitung antrean verifikasi
        $pendingPayments = Ticket::whereHas('event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        })->where('status', 'pending')
          ->whereNotNull('payment_proof')
          ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_events' => $totalEvents,
                'total_tickets_paid' => $totalTicketsPaid,
                'pending_payments' => $pendingPayments,
            ]
        ]);
    }

    // Lihat Daftar Antrean Verifikasi (Tiket berstatus 'pending')
    public function verifications(Request $request)
    {
        $organizerId = $request->user()->id;

        // Ambil tiket yang butuh verifikasi untuk event milik organizer ini
        $pendingTickets = Ticket::with(['user', 'event'])
            ->whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
            ->where('status', 'pending')
            ->whereNotNull('payment_proof')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $pendingTickets]);
    }

    // Approve Tiket
    public function approveTicket(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        // Pastikan tiket ada dan milik event dari organizer ini
        if (!$ticket || $ticket->event->organizer_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau akses ditolak.'], 404);
        }

        $ticket->update(['status' => 'paid']); // Status dirubah jadi 'paid' (Lunas)

        return response()->json(['status' => 'success', 'message' => 'Pembayaran tiket berhasil disetujui.']);
    }

    // Reject Tiket
    public function rejectTicket(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket || $ticket->event->organizer_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau akses ditolak.'], 404);
        }

        $ticket->update(['status' => 'cancelled']); // Status dirubah jadi 'cancelled'

        return response()->json(['status' => 'success', 'message' => 'Pembayaran tiket ditolak.']);
    }
}