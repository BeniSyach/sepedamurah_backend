<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
            // 'captcha' => 'required|string', // token dari frontend
        ]);

        // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret' => env('RECAPTCHA_SECRET_KEY'),
        //     'response' => $request->captcha,
        // ]);

        // $googleResponse = $response->json();

        // // Jika gagal verifikasi
        // if (!($googleResponse['success'] ?? false)) {
        //     return response()->json([
        //         'error' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.'
        //     ], 400);
        // }

         $user = User::with('rules')->where('nip', $request->nip)->first();

        if (!$user || $user->DELETED) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }

        // password plaintext
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Password salah'], 404);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout berhasil']);
    }
}
