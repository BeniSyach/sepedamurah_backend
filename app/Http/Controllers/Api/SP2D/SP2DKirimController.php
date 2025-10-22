<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DKirimModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DKirimResource;
use Illuminate\Support\Facades\DB;

class SP2DKirimController extends Controller
{
    /**
     * List SP2D Kirim (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SP2DKirimModel::query()->whereNull('deleted_at');

        if ($search = $request->get('search')) {
            $query->where('NAMA_PENERIMA', 'like', "%{$search}%")
                  ->orWhere('NAMA_OPERATOR', 'like', "%{$search}%")
                  ->orWhere('NAMAFILE', 'like', "%{$search}%");
        }

        $data = $query->orderBy('TANGGAL_UPLOAD', 'desc')
                      ->paginate($request->get('per_page', 10));

        return SP2DKirimResource::collection($data);
    }

    /**
     * Store SP2D Kirim baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'TAHUN' => 'required|string|max:4',
            'ID_BERKAS' => 'required|integer',
            'ID_PENERIMA' => 'required|integer',
            'NAMA_PENERIMA' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMAFILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KETERANGAN' => 'nullable|string|max:500',
            'DITERIMA' => 'nullable|date',
            'DITOLAK' => 'nullable|date',
            'TTE' => 'nullable|string|max:255',
            'STATUS' => 'nullable|string|max:50',
            'TGL_TTE' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'TGL_KIRIM_KEBANK' => 'nullable|date',
            'ID_PENANDATANGAN' => 'nullable|integer',
            'NAMA_PENANDATANGAN' => 'nullable|string|max:255',
            'FILE_TTE' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
            'PUBLISH' => 'nullable|string|max:50',
        ]);

        try {
            // Ambil ID dari sequence Oracle (jika ada trigger)
            $id = DB::connection('oracle')->selectOne('SELECT NO_SP2D_KIRIM.NEXTVAL AS ID FROM dual')->ID;

            $sp2d = SP2DKirimModel::create(array_merge($validated, [
                'ID' => $id,
                'created_at' => now(),
            ]));

            return new SP2DKirimResource($sp2d);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D Kirim
     */
    public function show($id)
    {
        $sp2d = SP2DKirimModel::where('ID', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DKirimResource($sp2d);
    }

    /**
     * Update SP2D Kirim
     */
    public function update(Request $request, $id)
    {
        $sp2d = SP2DKirimModel::where('ID', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'NAMA_PENERIMA' => 'required|string|max:255',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMAFILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'KETERANGAN' => 'nullable|string|max:500',
            'TTE' => 'nullable|string|max:255',
            'STATUS' => 'nullable|string|max:50',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PUBLISH' => 'nullable|string|max:50',
        ]);

        try {
            $sp2d->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SP2DKirimResource($sp2d),
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
     * Soft delete SP2D Kirim
     */
    public function destroy($id)
    {
        $sp2d = SP2DKirimModel::where('ID', $id)
                               ->whereNull('deleted_at')
                               ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $sp2d->deleted_at = now();
        $sp2d->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
