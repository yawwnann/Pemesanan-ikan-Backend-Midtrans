<?php

// File: app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrasi pengguna baru.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Validasi data input melalui RegisterRequest
        $validatedData = $request->validated();

        // Membuat pengguna baru
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']), // Pastikan password di-hash
        ]);

        // Menambahkan role 'user' ke user baru
        try {
            $userRole = Role::where('slug', 'user')->firstOrFail(); // Cari role dengan slug 'user'
            $user->roles()->attach($userRole->id); // Attach role ke user baru
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Role 'user' not found for registration user ID: " . $user->id . " - " . $e->getMessage());
        }

        // Membuat token baru untuk user
        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        // Mengembalikan response JSON dengan data user dan token
        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Melakukan login pengguna.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Validasi data input melalui LoginRequest
        $credentials = $request->validated();

        // Melakukan autentikasi dengan kredensial yang diberikan
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        // Ambil user setelah otentikasi berhasil
        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Muat hubungan roles yang dimiliki oleh user
        $user->load('roles');

        // Membuat token baru setelah login berhasil
        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        // Mengembalikan response JSON dengan data user dan token
        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout pengguna dan hapus token saat ini.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Menghapus token akses yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Mendapatkan data pengguna yang sedang login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        // Mengembalikan data pengguna yang sedang login
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
