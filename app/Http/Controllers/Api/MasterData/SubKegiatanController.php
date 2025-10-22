<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\SubKegiatanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SubKegiatanResource;

class SubKegiatanController extends Controller
{
    /**
     * Tampilkan daftar Sub Kegiatan (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = SubKegiatanModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_subkegiatan', 'like', "%{$search}%")
                ->orWhere('kd_subkeg1', 'like', "%{$search}%")
                ->orWhere('kd_subkeg2', 'like', "%{$search}%")
                ->orWhere('kd_subkeg3', 'like', "%{$search}%")
                ->orWhere('kd_subkeg4', 'like', "%{$search}%")
                ->orWhere('kd_subkeg5', 'like', "%{$search}%")
                ->orWhere('kd_subkeg6', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return SubKegiatanResource::collection($data);
    }

    /**
     * Simpan data Sub Kegiatan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_subkeg1'     => 'required|string|max:10',
            'kd_subkeg2'     => 'required|string|max:10',
            'kd_subkeg3'     => 'required|string|max:10',
            'kd_subkeg4'     => 'required|string|max:10',
            'kd_subkeg5'     => 'required|string|max:10',
            'kd_subkeg6'     => 'required|string|max:10',
            'nm_subkegiatan' => 'required|string|max:255',
        ]);

        try {
            $subkegiatan = SubKegiatanModel::create($validated);

            return new SubKegiatanResource($subkegiatan);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode sub kegiatan sudah terdaftar.',
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
     * Tampilkan detail Sub Kegiatan.
     */
    public function show($kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $subkegiatan = DB::connection('oracle')
                ->table(DB::raw('REF_SUBKEGIATAN'))
                ->whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$subkegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $subkegiatan,
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
     * Update data Sub Kegiatan.
     */
    public function update(Request $request, $kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $subkegiatan = SubKegiatanModel::whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->first();

            if (!$subkegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_subkegiatan' => 'required|string|max:255',
            ]);

            $subkegiatan->nm_subkegiatan = $validated['nm_subkegiatan'];
            $subkegiatan->save();

            return response()->json([
                'status' => true,
                'message' => 'Data sub kegiatan berhasil diperbarui',
                'data' => new SubKegiatanResource($subkegiatan),
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
     * Soft delete data Sub Kegiatan.
     */
    public function destroy($kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_SUBKEGIATAN')
                ->whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data sub kegiatan berhasil dihapus (soft delete)',
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
