<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
        ]);

        return response()->json([
            'message'   => 'User registered successfully',
            'user'      => $user
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('Mini-Warehouse');

        return response()->json([
            'message'       => 'Login Success.',
            'access_token'  => $token->accessToken,
            'token_type'    => $token->tokenType,
            'expires_in'    => $token->expiresIn,
            'user'          => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}