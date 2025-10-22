<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\SKPDModel;
use Illuminate\Http\Request;
use App\Http\Resources\SKPDResource;
use Illuminate\Support\Facades\DB;

class SKPDController extends Controller
{
    /**
     * Tampilkan daftar SKPD (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SKPDModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_opd', 'like', "%{$search}%")
                  ->orWhere('kd_opd1', 'like', "%{$search}%")
                  ->orWhere('kd_opd2', 'like', "%{$search}%")
                  ->orWhere('kd_opd3', 'like', "%{$search}%")
                  ->orWhere('kd_opd4', 'like', "%{$search}%")
                  ->orWhere('kd_opd5', 'like', "%{$search}%");
        }

        $data = $query->orderBy('kd_opd1')
                      ->orderBy('kd_opd2')
                      ->orderBy('kd_opd3')
                      ->orderBy('kd_opd4')
                      ->orderBy('kd_opd5')
                      ->paginate($request->get('per_page', 10));

        return SKPDResource::collection($data);
    }

    /**
     * Simpan SKPD baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required|string|max:10',
            'kd_opd2' => 'required|string|max:10',
            'kd_opd3' => 'required|string|max:10',
            'kd_opd4' => 'required|string|max:10',
            'kd_opd5' => 'required|string|max:10',
            'nm_opd' => 'required|string|max:255',
            'status_penerimaan' => 'nullable|integer',
            'kode_opd' => 'nullable|string|max:50',
            'hidden' => 'nullable|integer',
        ]);

        try {
            $skpd = SKPDModel::create($validated);
            return new SKPDResource($skpd);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data SKPD sudah terdaftar.',
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
     * Tampilkan detail SKPD
     */
    public function show($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PENGEMBALIAN.REF_OPD'))
                ->whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data SKPD tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
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
     * Update data SKPD
     */
    public function update(Request $request, $kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5)
    {
        try {
            $skpd = SKPDModel::whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
                ->first();

            if (!$skpd) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data SKPD tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_opd' => 'required|string|max:255',
                'status_penerimaan' => 'nullable|integer',
                'kode_opd' => 'nullable|string|max:50',
                'hidden' => 'nullable|integer',
            ]);

            $skpd->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data SKPD berhasil diperbarui',
                'data' => new SKPDResource($skpd),
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
     * Soft delete SKPD
     */
    public function destroy($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PENGEMBALIAN.REF_OPD')
                ->whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
                ->update(['deleted_at' => now()]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data SKPD tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data SKPD berhasil dihapus (soft delete)',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
