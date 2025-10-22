<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PersetujuanModel;
use Illuminate\Http\Request;
use App\Http\Resources\PersetujuanResource;
use Illuminate\Support\Facades\DB;

class PersetujuanController extends Controller
{
    /**
     * Tampilkan daftar Persetujuan (pagination + search)
     */
    public function index(Request $request)
    {
        $query = PersetujuanModel::query();

        if ($search = $request->get('search')) {
            $query->where('konten', 'like', "%{$search}%");
        }

        $data = $query->orderBy('id', 'asc')
                      ->paginate($request->get('per_page', 10));

        return PersetujuanResource::collection($data);
    }

    /**
     * Simpan Persetujuan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'konten' => 'required|string',
        ]);

        try {
            $persetujuan = PersetujuanModel::create($validated);

            return new PersetujuanResource($persetujuan);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Persetujuan sudah terdaftar.',
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
     * Tampilkan detail Persetujuan
     */
    public function show($id)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PENGEMBALIAN.AGGREMENT'))
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Persetujuan tidak ditemukan',
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
     * Update data Persetujuan
     */
    public function update(Request $request, $id)
    {
        try {
            $persetujuan = PersetujuanModel::find($id);

            if (!$persetujuan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Persetujuan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'konten' => 'sometimes|required|string',
            ]);

            $persetujuan->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data Persetujuan berhasil diperbarui',
                'data' => new PersetujuanResource($persetujuan),
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
     * Soft delete data Persetujuan
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PENGEMBALIAN.AGGREMENT')
                ->where('id', $id)
                ->update(['deleted_at' => now()]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Persetujuan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Persetujuan berhasil dihapus (soft delete)',
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
