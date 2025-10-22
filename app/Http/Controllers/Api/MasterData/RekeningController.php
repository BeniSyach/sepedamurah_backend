<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekeningModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RekeningResource;

class RekeningController extends Controller
{
    /**
     * Tampilkan daftar Rekening (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = RekeningModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_rekening', 'like', "%{$search}%")
                ->orWhere('kd_rekening1', 'like', "%{$search}%")
                ->orWhere('kd_rekening2', 'like', "%{$search}%")
                ->orWhere('kd_rekening3', 'like', "%{$search}%")
                ->orWhere('kd_rekening4', 'like', "%{$search}%")
                ->orWhere('kd_rekening5', 'like', "%{$search}%")
                ->orWhere('kd_rekening6', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return RekeningResource::collection($data);
    }

    /**
     * Simpan data Rekening baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_rekening1' => 'required|string|max:10',
            'kd_rekening2' => 'required|string|max:10',
            'kd_rekening3' => 'required|string|max:10',
            'kd_rekening4' => 'required|string|max:10',
            'kd_rekening5' => 'required|string|max:10',
            'kd_rekening6' => 'required|string|max:10',
            'nm_rekening'  => 'required|string|max:255',
        ]);

        try {
            $rekening = RekeningModel::create($validated);

            return new RekeningResource($rekening);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode rekening sudah terdaftar.',
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
     * Tampilkan detail Rekening.
     */
    public function show($kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $rekening = DB::connection('oracle')
                ->table(DB::raw('REF_REKENING'))
                ->whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$rekening) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $rekening,
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
     * Update data Rekening.
     */
    public function update(Request $request, $kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $rekening = RekeningModel::whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->first();

            if (!$rekening) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_rekening' => 'required|string|max:255',
            ]);

            $rekening->nm_rekening = $validated['nm_rekening'];
            $rekening->save();

            return response()->json([
                'status' => true,
                'message' => 'Data rekening berhasil diperbarui',
                'data' => new RekeningResource($rekening),
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
     * Soft delete data Rekening.
     */
    public function destroy($kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_REKENING')
                ->whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data rekening berhasil dihapus (soft delete)',
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
