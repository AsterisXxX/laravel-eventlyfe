<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Tampilkan semua daftar event (Untuk Halaman Home/Discover Flutter)
     */
    public function index(Request $request)
    {
        try {
            $query = Event::with('category')->latest();

            // Sama seperti di Web: Filter Pencarian Nama
            if ($request->has('search') && $request->search != '') {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Sama seperti di Web: Filter Lokasi
            if ($request->has('location') && $request->location != '') {
                $query->where('location', $request->location);
            }

            $events = $query->get(); // Di API biasanya kita pakai get() langsung atau paginate() JSON

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
        // Eager load kategori agar data icon/warna tersedia di Flutter
        $event = Event::with('category')->find($id);

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
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'price' => 'required|numeric',
            'quota' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            // Logika Upload ke public/images/events persis seperti di Web
            $imageName = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/events'), $imageName);
            }

            $event = Event::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'location' => $request->location,
                'date' => $request->date,
                'price' => $request->price,
                'quota' => $request->quota,
                'image' => $imageName,
                'description' => $request->description,
                'organizer_id' => $request->user()->id, // Menggunakan ID user yang sedang login via Token API
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Event baru berhasil dibuat!',
                'data' => $event
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan event.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update/Edit Event Yang Sudah Ada (Organizer Area)
     */
    public function update(Request $request, $id)
    {
        // Pastikan event milik organizer yang merequest
        $event = Event::where('id', $id)->where('organizer_id', $request->user()->id)->first();

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event tidak ditemukan atau Anda tidak memiliki akses.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location' => 'required|string|max:255',
            'date' => 'required',
            'price' => 'required|numeric',
            'quota' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            $data['slug'] = Str::slug($request->name);

            // Logika Penggantian Gambar persis seperti di Web
            if ($request->hasFile('image')) {
                // Hapus gambar lama
                $oldPath = public_path('images/events/' . $event->image);
                if ($event->image && File::exists($oldPath)) {
                    File::delete($oldPath);
                }

                // Simpan gambar baru
                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/events'), $imageName);
                $data['image'] = $imageName;
            } else {
                $data['image'] = $event->image;
            }

            $event->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Event berhasil diperbarui!',
                'data' => $event
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui event.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus Event (Organizer Area)
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::where('id', $id)->where('organizer_id', $request->user()->id)->first();

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event tidak ditemukan atau akses ditolak.'], 404);
        }

        try {
            // Hapus file gambar dari folder persis seperti di Web
            if ($event->image) {
                $filePath = public_path('images/events/' . $event->image);
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event berhasil dihapus secara permanen.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus event.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign Checkers API
     */
    public function assignCheckers(Request $request, $id)
    {
        $event = Event::where('id', $id)->where('organizer_id', $request->user()->id)->first();

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'checker_ids' => 'required|array',
            'checker_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $event->checkers()->sync($request->checker_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Tim Checker berhasil ditugaskan!'
        ], 200);
    }
}
