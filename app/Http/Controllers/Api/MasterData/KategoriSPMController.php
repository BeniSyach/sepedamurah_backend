<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\KategoriSPMModel;
use Illuminate\Http\Request;
use App\Http\Resources\KategoriSPMResource;
use Illuminate\Support\Facades\DB;

class KategoriSPMController extends Controller
{
    /**
     * Tampilkan daftar Kategori SPM (pagination + search)
     */
    public function index(Request $request)
    {
        $query = KategoriSPMModel::query();

        if ($search = $request->get('search')) {
            $query->where('kategori', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
        }

        $data = $query->orderBy('id', 'asc')
                      ->paginate($request->get('per_page', 10));

        return KategoriSPMResource::collection($data);
    }

    /**
     * Simpan Kategori SPM baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|string|max:50',
            'status' => 'required|boolean',
        ]);

        try {
            $kategori = KategoriSPMModel::create($validated);

            return new KategoriSPMResource($kategori);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Kategori SPM sudah terdaftar.',
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
     * Tampilkan detail Kategori SPM
     */
    public function show($id)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PENGEMBALIAN.KATEGORI_SPM'))
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Kategori SPM tidak ditemukan',
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
     * Update data Kategori SPM
     */
    public function update(Request $request, $id)
    {
        try {
            $kategori = KategoriSPMModel::find($id);

            if (!$kategori) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Kategori SPM tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'kategori' => 'sometimes|required|string|max:50',
                'status' => 'sometimes|required|boolean',
            ]);

            $kategori->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data Kategori SPM berhasil diperbarui',
                'data' => new KategoriSPMResource($kategori),
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
     * Soft delete data Kategori SPM
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PENGEMBALIAN.KATEGORI_SPM')
                ->where('id', $id)
                ->update(['deleted_at' => now()]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Kategori SPM tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Kategori SPM berhasil dihapus (soft delete)',
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
