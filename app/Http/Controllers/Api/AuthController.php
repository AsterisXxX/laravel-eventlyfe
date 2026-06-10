<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Handle Login API
     */
    public function login(Request $request)
    {
        // Validasi input awal
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->input('login');

        // Cek apakah input adalah email yang valid, persis seperti di web
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cari user beserta rolenya
        $user = User::with('role')->where($field, $login)->first();

        // Cek kecocokan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Username atau password salah.'
            ], 401);
        }

        // Generate Token menggunakan Sanctum
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user,
                'role' => $user->role->slug, // Sangat berguna untuk routing di mobile (User/Checker/Organizer)
                'token' => $token
            ]
        ], 200);
    }

    /**
     * Handle Register API
     */
    public function register(Request $request)
    {
        // Validasi sama persis dengan aturan di web
        $validator = Validator::make($request->all(), [
            'username'  => ['required', 'string', 'alpha_dash', 'max:255', 'unique:users'],
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role'      => ['nullable', 'string', 'in:user,organizer'],
            'password'  => [
                'required',
                'string',
                'confirmed', // Pastikan di mobile mengirimkan 'password_confirmation'
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil role, default ke user
        $roleSlug = $request->input('role', 'user');
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role tidak valid.'
            ], 404);
        }

        // Create User
        $user = User::create([
            'username'  => $request->username,
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role_id'   => $role->id,
        ]);

        // Load relasi role agar response lengkap
        $user->load('role');

        // Generate Token langsung setelah register agar otomatis login
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'data' => [
                'user' => $user,
                'role' => $roleSlug,
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Handle Logout API
     */
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ], 200);
    }
}
