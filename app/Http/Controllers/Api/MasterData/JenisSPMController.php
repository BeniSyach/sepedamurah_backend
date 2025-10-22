<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\JenisSPMModel;
use Illuminate\Http\Request;
use App\Http\Resources\JenisSPMResource;
use Illuminate\Support\Facades\DB;

class JenisSPMController extends Controller
{
    /**
     * Tampilkan daftar Jenis SPM (pagination + search)
     */
    public function index(Request $request)
    {
        $query = JenisSPMModel::query();

        if ($search = $request->get('search')) {
            $query->where('kategori', 'like', "%{$search}%")
                  ->orWhere('nama_berkas', 'like', "%{$search}%");
        }

        $data = $query->orderBy('id', 'asc')
                      ->paginate($request->get('per_page', 10));

        return JenisSPMResource::collection($data);
    }

    /**
     * Simpan Jenis SPM baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|string|max:50',
            'nama_berkas' => 'required|string|max:255',
            'status_penerimaan' => 'required|boolean',
        ]);

        try {
            $jenisSpm = JenisSPMModel::create($validated);

            return new JenisSPMResource($jenisSpm);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis SPM sudah terdaftar.',
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
     * Tampilkan detail Jenis SPM
     */
    public function show($id)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PENGEMBALIAN.REF_JENIS_SPM'))
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis SPM tidak ditemukan',
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
     * Update data Jenis SPM
     */
    public function update(Request $request, $id)
    {
        try {
            $jenisSpm = JenisSPMModel::find($id);

            if (!$jenisSpm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis SPM tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'kategori' => 'sometimes|required|string|max:50',
                'nama_berkas' => 'sometimes|required|string|max:255',
                'status_penerimaan' => 'sometimes|required|boolean',
            ]);

            $jenisSpm->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data Jenis SPM berhasil diperbarui',
                'data' => new JenisSPMResource($jenisSpm),
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
     * Soft delete data Jenis SPM
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PENGEMBALIAN.REF_JENIS_SPM')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis SPM tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Jenis SPM berhasil dihapus (soft delete)',
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
