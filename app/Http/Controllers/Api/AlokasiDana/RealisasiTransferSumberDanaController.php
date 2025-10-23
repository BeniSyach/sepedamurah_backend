<?php

namespace App\Http\Controllers\Api\AlokasiDana;

use App\Http\Controllers\Controller;
use App\Models\RealisasiSumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\RealisasiSumberDanaResource;
use Illuminate\Support\Facades\DB;

class RealisasiTransferSumberDanaController extends Controller
{
    /**
     * Tampilkan daftar sumber dana (dengan pagination + search)
     */
    public function index(Request $request)
    {
        $query = RealisasiSumberDanaModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_sumber', 'like', "%{$search}%")
                  ->orWhere('tahun', 'like', "%{$search}%");
        }

        $data = $query->orderBy('tgl_diterima', 'desc')->paginate($request->get('per_page', 10));

        return RealisasiSumberDanaResource::collection($data);
    }

    /**
     * Simpan sumber dana baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_ref1' => 'required|string|max:1',
            'kd_ref2' => 'required|string|max:1',
            'kd_ref3' => 'nullable|string|max:2',
            'kd_ref4' => 'nullable|string|max:2',
            'kd_ref5' => 'nullable|string|max:2',
            'kd_ref6' => 'nullable|string|max:4',
            'nm_sumber' => 'required|string|max:300',
            'tgl_diterima' => 'required|date',
            'tahun' => 'required|string|max:4',
            'jumlah_sumber' => 'nullable|numeric',
            'keterangan' => 'required|integer',
            'keterangan_2' => 'nullable|string|max:255',
        ]);

        try {
            $sumber = RealisasiSumberDanaModel::create($validated);
            return new RealisasiSumberDanaResource($sumber);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data dengan kombinasi key sudah terdaftar.',
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
     * Tampilkan detail sumber dana.
     */
    public function show($id)
    {
        try {
            $sumber = DB::connection('oracle')
                ->table(DB::raw('SUMBER_DANA'))
                ->whereRaw('ID = ?', [$id])
                ->first();

            if (!$sumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $sumber,
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
     * Update sumber dana.
     */
    public function update(Request $request, $id)
    {
        try {
            $sumber = RealisasiSumberDanaModel::find($id);

            if (!$sumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'kd_ref1' => 'required|string|max:1',
                'kd_ref2' => 'required|string|max:1',
                'kd_ref3' => 'nullable|string|max:2',
                'kd_ref4' => 'nullable|string|max:2',
                'kd_ref5' => 'nullable|string|max:2',
                'kd_ref6' => 'nullable|string|max:4',
                'nm_sumber' => 'required|string|max:300',
                'tgl_diterima' => 'required|date',
                'tahun' => 'required|string|max:4',
                'jumlah_sumber' => 'nullable|numeric',
                'keterangan' => 'required|integer',
                'keterangan_2' => 'nullable|string|max:255',
            ]);

            $sumber->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new RealisasiSumberDanaResource($sumber),
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
     * Soft delete sumber dana.
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('SUMBER_DANA')
                ->where('ID', $id)
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus (soft delete)',
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
