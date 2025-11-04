<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\BidangUrusanModel;
use Illuminate\Http\Request;
use App\Http\Resources\BidangUrusanResource;
use Illuminate\Support\Facades\DB;

class BidangUrusanController extends Controller
{
    /**
     * Tampilkan daftar Bidang Urusan (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = BidangUrusanModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_bu', 'like', "%{$search}%")
                  ->orWhere('kd_bu1', 'like', "%{$search}%")
                  ->orWhere('kd_bu2', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return BidangUrusanResource::collection($data);
    }

    /**
     * Simpan data Bidang Urusan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_bu1' => 'required|string|max:10',
            'kd_bu2' => 'required|string|max:10',
            'nm_bu'  => 'required|string|max:255',
        ]);

        try {
            $bidang = BidangUrusanModel::create($validated);

            return new BidangUrusanResource($bidang);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode bidang urusan sudah terdaftar.',
                ], 409);
            }

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tampilkan detail Bidang Urusan.
     */
    public function show($kd_bu1, $kd_bu2)
    {
        try {
            $bidang = DB::connection('oracle')
                ->table(DB::raw('REF_BIDANG_URUSAN'))
                ->whereRaw('TRIM(KD_BU1) = ?', [trim($kd_bu1)])
                ->whereRaw('TRIM(KD_BU2) = ?', [trim($kd_bu2)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$bidang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data bidang urusan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $bidang,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data Bidang Urusan.
     */
    public function update(Request $request, $kd_bu1, $kd_bu2)
    {
        try {
            $bidang = BidangUrusanModel::whereRaw('TRIM(KD_BU1) = ?', [trim($kd_bu1)])
                ->whereRaw('TRIM(KD_BU2) = ?', [trim($kd_bu2)])
                ->first();

            if (!$bidang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data bidang urusan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_bu' => 'required|string|max:255',
            ]);

            $bidang->nm_bu = $validated['nm_bu'];
            $bidang->save();

            return response()->json([
                'status' => true,
                'message' => 'Data bidang urusan berhasil diperbarui',
                'data' => new BidangUrusanResource($bidang),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete data Bidang Urusan.
     */
    public function destroy($kd_bu1, $kd_bu2)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_BIDANG_URUSAN')
                ->whereRaw('TRIM(KD_BU1) = ?', [trim($kd_bu1)])
                ->whereRaw('TRIM(KD_BU2) = ?', [trim($kd_bu2)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data bidang urusan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data bidang urusan berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_bidang_urusan_sp2d(Request $request)
    {
        // Ambil user dari JWT token
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'User tidak terautentikasi'], 401);
        }
    
        // Ambil parameter kd_urusan dari request
        $kd_urusan = $request->input('kd_urusan');
    
        if (!$kd_urusan) {
            return response()->json(['error' => 'Parameter kd_urusan wajib diisi'], 400);
        }
    
        // Query builder Laravel setara dengan SQL CodeIgniter kamu
        $query = DB::table('REF_BIDANG_URUSAN')
            ->distinct()
            ->select('REF_BIDANG_URUSAN.*')
            ->join('PAGU_BELANJA', function ($join) {
                $join->on('REF_BIDANG_URUSAN.KD_BU1', '=', 'PAGU_BELANJA.KD_BU1')
                     ->on('REF_BIDANG_URUSAN.KD_BU2', '=', 'PAGU_BELANJA.KD_BU2');
            })
            ->join('REF_OPD', function ($join) {
                $join->on(DB::raw("
                    LOWER(REPLACE(COALESCE(REF_OPD.KODE_OPD, ''), ' ', ''))
                "), '=', DB::raw("
                    LOWER(REPLACE(
                        COALESCE(PAGU_BELANJA.KD_OPD1, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD2, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD3, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD4, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD5, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD6, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD7, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD8, '')
                    , ' ', ''))
                "));
            })
            ->where('REF_OPD.KD_OPD1', $user->kd_opd1)
            ->where('REF_OPD.KD_OPD2', $user->kd_opd2)
            ->where('REF_OPD.KD_OPD3', $user->kd_opd3)
            ->where('REF_OPD.KD_OPD4', $user->kd_opd4)
            ->where('REF_OPD.KD_OPD5', $user->kd_opd5)
            ->where('REF_OPD.HIDDEN', 0)
            ->where('PAGU_BELANJA.KD_URUSAN', 'LIKE', $kd_urusan . '%')
            ->where('PAGU_BELANJA.IS_DELETED', 0)
            ->get();
    
            return response()->json([
                'data' => $query
            ]);
    }
}
