<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BerkasLainModel;
use App\Http\Resources\BerkasLainResource;

class BerkasLainController extends Controller
{
    /**
     * List Berkas Lain (pagination + search)
     */
    public function index(Request $request)
    {
        $query = BerkasLainModel::query()->whereNull('deleted_at');

        if ($search = $request->get('search')) {
            $query->where('nama_dokumen', 'like', "%{$search}%")
                  ->orWhere('nama_file_asli', 'like', "%{$search}%");
        }

        $data = $query->orderBy('tgl_surat', 'desc')
                      ->paginate($request->get('per_page', 10));

        return BerkasLainResource::collection($data);
    }

    /**
     * Store Berkas Lain baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tgl_surat' => 'required|date',
            'nama_file_asli' => 'required|string|max:255',
            'nama_dokumen' => 'required|string|max:255',
            'status_tte' => 'nullable|string|max:50',
            'file_sdh_tte' => 'nullable|string|max:255',
            'users_id' => 'required|integer',
        ]);

        try {
            $berkas = BerkasLainModel::create($validated);

            return new BerkasLainResource($berkas);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show detail Berkas Lain
     */
    public function show($id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();

        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new BerkasLainResource($berkas);
    }

    /**
     * Update Berkas Lain
     */
    public function update(Request $request, $id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();

        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'tgl_surat' => 'required|date',
            'nama_file_asli' => 'required|string|max:255',
            'nama_dokumen' => 'required|string|max:255',
            'status_tte' => 'nullable|string|max:50',
            'file_sdh_tte' => 'nullable|string|max:255',
            'users_id' => 'required|integer',
        ]);

        try {
            $berkas->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new BerkasLainResource($berkas),
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
     * Soft delete Berkas Lain
     */
    public function destroy($id)
    {
        $berkas = BerkasLainModel::where('id', $id)
                                 ->whereNull('deleted_at')
                                 ->first();

        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $berkas->deleted_at = now();
        $berkas->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
