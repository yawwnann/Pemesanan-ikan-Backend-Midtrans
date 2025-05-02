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
        // Validasi dan simpan pengguna baru
        $validatedData = $request->validated();
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Attach role 'user' ke pengguna baru
        try {
            $userRole = Role::where('slug', 'user')->firstOrFail();
            $user->roles()->attach($userRole->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Role 'user' not found: " . $e->getMessage());
        }

        // Generate token
        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login pengguna.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Validasi dan autentikasi pengguna
        $credentials = $request->validated();
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        // Ambil user dan generate token
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $user->load('roles');
        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout pengguna dan hapus token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
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
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
