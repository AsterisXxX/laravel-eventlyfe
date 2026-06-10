<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Tampilkan semua daftar event (Untuk Halaman Home/Discover)
     */
    public function index()
    {
        try {
            // Mengambil event terbaru
            $events = Event::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar event berhasil dimuat.',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data event.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan detail dari satu event
     */
    public function show($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail event ditemukan.',
            'data' => $event
        ], 200);
    }

    /**
     * Tambah Event Baru (Organizer Area)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string', // Sementara string URL/Path, nanti bisa di-upgrade ke upload file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event = Event::create([
                'title' => $request->title,
                'price' => $request->price,
                'image' => $request->image ?? '',
                // Jika event berelasi dengan user/organizer yang membuatnya, bisa tambahkan:
                // 'user_id' => $request->user()->id 
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Event baru berhasil dibuat!',
                'data' => $event
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan event.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update/Edit Event Yang Sudah Ada (Organizer Area)
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event->update([
                'title' => $request->title,
                'price' => $request->price,
                'image' => $request->image ?? $event->image,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Event berhasil diperbarui!',
                'data' => $event
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui event.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus Event (Organizer Area)
     */
    public function destroy($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event tidak ditemukan.'
            ], 404);
        }

        try {
            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus event.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
