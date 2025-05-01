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
    public function register(RegisterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
        ]);

        try {
            $userRole = Role::where('slug', 'user')->firstOrFail(); // Cari role dgn slug 'user'
            $user->roles()->attach($userRole->id); // Attach role ke user baru
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Role 'user' not found for registration user ID: " . $user->id . " - " . $e->getMessage());
        }

        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        // Ambil user setelah otentikasi berhasil
        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Hapus token lama (opsional, untuk single login)
        // $user->tokens()->delete();

        $user->load('roles');

        // Buat token baru
        $token = $user->createToken('api-token-' . $user->id)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}