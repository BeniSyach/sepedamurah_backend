<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'nip' => 'required|string',
    //         'password' => 'required|string',
    //         'captcha' => 'required|string', // token dari frontend
    //     ]);

    //     $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
    //         'secret' => env('RECAPTCHA_SECRET_KEY'),
    //         'response' => $request->captcha,
    //     ]);

    //     $googleResponse = $response->json();

    //     // Jika gagal verifikasi
    //     if (!($googleResponse['success'] ?? false)) {
    //         return response()->json([
    //             'error' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.'
    //         ], 400);
    //     }

    //     $user = User::select('users.*', 'ref_opd.nama_opd', 'ref_opd.status_penerimaan')
    //     ->join('ref_opd', function ($join) {
    //         $join->on('users.kd_opd1', '=', 'ref_opd.kd_opd1')
    //             ->on('users.kd_opd2', '=', 'ref_opd.kd_opd2')
    //             ->on('users.kd_opd3', '=', 'ref_opd.kd_opd3')
    //             ->on('users.kd_opd4', '=', 'ref_opd.kd_opd4')
    //             ->on('users.kd_opd5', '=', 'ref_opd.kd_opd5');
    //     })
    //     ->where('users.nip', $request->nip)
    //     ->whereNull('users.deleted_at')
    //     ->first();
    //     if (!$user) {
    //         return response()->json([
    //             'error' => 'User tidak ditemukan atau SKPD tidak aktif'
    //         ], 404);
    //     }
    
    //     if (!Hash::check($request->password, $user->password)) {
    //         return response()->json(['error' => 'NIP atau Password salah'], 404);
    //     }
    
    //     $token = JWTAuth::fromUser($user);
    
    //     // Ambil rule + menu seperti biasa
    //     $user->load(['rules.menus']);

    //     return response()->json([
    //         'user' => $user,
    //         'token' => $token
    //     ]);
    // }

    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|string',
        ]);
    
        // ================================
        // VALIDASI RECAPTCHA
        // ================================
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->captcha,
        ]);
    
        $googleResponse = $response->json();
    
        if (!($googleResponse['success'] ?? false)) {
            return response()->json([
                'error' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.'
            ], 400);
        }
    
        // ================================
        // AMBIL USER + STATUS PENERIMAAN
        // ================================
        $user = User::select(
            'users.*',
            'ref_opd.nm_opd',
            'ref_opd.status_penerimaan',
            'ref_opd.kd_opd1 as skpd_kd_opd1',
            'ref_opd.kd_opd2 as skpd_kd_opd2',
            'ref_opd.kd_opd3 as skpd_kd_opd3',
            'ref_opd.kd_opd4 as skpd_kd_opd4',
            'ref_opd.kd_opd5 as skpd_kd_opd5',
            'ref_opd.kode_opd',
            'ref_opd.hidden',
            'ref_opd.created_at as skpd_created_at',
            'ref_opd.updated_at as skpd_updated_at'
        )
        ->join('ref_opd', function ($join) {
            $join->on('users.kd_opd1', '=', 'ref_opd.kd_opd1')
                ->on('users.kd_opd2', '=', 'ref_opd.kd_opd2')
                ->on('users.kd_opd3', '=', 'ref_opd.kd_opd3')
                ->on('users.kd_opd4', '=', 'ref_opd.kd_opd4')
                ->on('users.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->where('users.nip', $request->nip)
        ->where('users.deleted', '0')
        ->first();

        $skpds = User::join('ref_opd', function ($join) {
            $join->on('users.kd_opd1', '=', 'ref_opd.kd_opd1')
                 ->on('users.kd_opd2', '=', 'ref_opd.kd_opd2')
                 ->on('users.kd_opd3', '=', 'ref_opd.kd_opd3')
                 ->on('users.kd_opd4', '=', 'ref_opd.kd_opd4')
                 ->on('users.kd_opd5', '=', 'ref_opd.kd_opd5');
        })
        ->where('users.nip', $request->nip)
        ->where('users.deleted', '0')
        ->select(
            'ref_opd.kd_opd1',
            'ref_opd.kd_opd2',
            'ref_opd.kd_opd3',
            'ref_opd.kd_opd4',
            'ref_opd.kd_opd5',
            'ref_opd.nm_opd',
            'ref_opd.kode_opd',
            'ref_opd.status_penerimaan'
        )
        ->distinct()
        ->map(function ($row) {
            return [
                'kd_opd1' => (string) $row->kd_opd1,
                'kd_opd2' => (string) $row->kd_opd2,
                'kd_opd3' => (string) $row->kd_opd3,
                'kd_opd4' => (string) $row->kd_opd4,
                'kd_opd5' => (string) $row->kd_opd5,
                'nm_opd'  => $row->nm_opd,
                'is_active' => (string) $row->is_active,
            ];
        });
    
    
        if (!$user) {
            return response()->json([
                'error' => 'User tidak ditemukan atau SKPD tidak aktif'
            ], 404);
        }
    
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'NIP atau Password salah'], 404);
        }
    
        // =================================
        // SIMPAN STATUS PENERIMAAN SEBELUM LOAD RELATIONS
        // =================================
        $statusPenerimaan = $user->status_penerimaan;
    
        // =================================
        // GENERATE TOKEN
        // =================================
        $token = JWTAuth::fromUser($user);
    
        // =================================
        // LOAD RULES + MENUS
        // =================================
        $user->load(['rules' => function ($query) use ($statusPenerimaan) {
            $query->with(['menus' => function ($menuQuery) use ($statusPenerimaan) {
                // Filter di level query
                if ($statusPenerimaan == 0 || $statusPenerimaan == '0') {
                    // Hilangkan menu penerimaan
                    $menuQuery->where('menu', 'NOT LIKE', '%penerimaan%');
                }
            }]);
        }]);
    
        // =================================
        // BUILD SKPD OBJECT UNTUK RESPONSE
        // =================================
        
        $user->skpds = $skpds;

        // $user->skpd = $skpds->map(function ($s) {
        //     return [
        //         'kd_opd1' => $s->kd_opd1,
        //         'kd_opd2' => $s->kd_opd2,
        //         'kd_opd3' => $s->kd_opd3,
        //         'kd_opd4' => $s->kd_opd4,
        //         'kd_opd5' => $s->kd_opd5,
        //         'nm_opd' => $s->nm_opd,
        //         'kode_opd' => $s->kode_opd,
        //         'status_penerimaan' => (int) $s->status_penerimaan,
        //         'hidden' => (int) $s->hidden,
        //         'created_at' => $s->created_at,
        //         'updated_at' => $s->updated_at,
        //     ];
        // });
        
    
        // Hapus kolom duplikat dari user level
        // unset(
        //     $user->skpd_kd_opd1, 
        //     $user->skpd_kd_opd2, 
        //     $user->skpd_kd_opd3, 
        //     $user->skpd_kd_opd4, 
        //     $user->skpd_kd_opd5, 
        //     $user->skpd_created_at, 
        //     $user->skpd_updated_at, 
        //     $user->kode_opd, 
        //     $user->hidden
        // );
    
        // =================================
        // RETURN RESPONSE
        // =================================
        return response()->json([
            'user' => $user,
            'token' => $token,
            'status' => (string) $statusPenerimaan
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
