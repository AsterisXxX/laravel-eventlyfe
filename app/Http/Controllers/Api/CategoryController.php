<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category; // Pastikan model Category sudah ada
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Ambil semua data kategori dari database
        $categories = Category::all();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ], 200);
    }
}
