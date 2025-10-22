<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\KegiatanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\KegiatanResource;

class KegiatanController extends Controller
{
    /**
     * Tampilkan daftar Kegiatan (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = KegiatanModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_kegiatan', 'like', "%{$search}%")
                ->orWhere('kd_keg1', 'like', "%{$search}%")
                ->orWhere('kd_keg2', 'like', "%{$search}%")
                ->orWhere('kd_keg3', 'like', "%{$search}%")
                ->orWhere('kd_keg4', 'like', "%{$search}%")
                ->orWhere('kd_keg5', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return KegiatanResource::collection($data);
    }

    /**
     * Simpan data Kegiatan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_keg1'     => 'required|string|max:10',
            'kd_keg2'     => 'required|string|max:10',
            'kd_keg3'     => 'required|string|max:10',
            'kd_keg4'     => 'required|string|max:10',
            'kd_keg5'     => 'required|string|max:10',
            'nm_kegiatan' => 'required|string|max:255',
        ]);

        try {
            $kegiatan = KegiatanModel::create($validated);

            return new KegiatanResource($kegiatan);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode kegiatan sudah terdaftar.',
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
     * Tampilkan detail Kegiatan.
     */
    public function show($kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $kegiatan = DB::connection('oracle')
                ->table(DB::raw('REF_KEGIATAN'))
                ->whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$kegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $kegiatan,
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
     * Update data Kegiatan.
     */
    public function update(Request $request, $kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $kegiatan = KegiatanModel::whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->first();

            if (!$kegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_kegiatan' => 'required|string|max:255',
            ]);

            $kegiatan->nm_kegiatan = $validated['nm_kegiatan'];
            $kegiatan->save();

            return response()->json([
                'status' => true,
                'message' => 'Data kegiatan berhasil diperbarui',
                'data' => new KegiatanResource($kegiatan),
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
     * Soft delete data Kegiatan.
     */
    public function destroy($kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_KEGIATAN')
                ->whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data kegiatan berhasil dihapus (soft delete)',
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
