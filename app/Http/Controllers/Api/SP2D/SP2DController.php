<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DResource;
use Illuminate\Support\Facades\DB;

class SP2DController extends Controller
{
    /**
     * List SP2D (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SP2DModel::query()->whereNull('deleted_at');

        if ($search = $request->get('search')) {
            $query->where('NAMA_USER', 'like', "%{$search}%")
                  ->orWhere('NAMA_OPERATOR', 'like', "%{$search}%")
                  ->orWhere('NAMA_FILE', 'like', "%{$search}%");
        }

        $data = $query->orderBy('TANGGAL_UPLOAD', 'desc')
                      ->paginate($request->get('per_page', 10));

        return SP2DResource::collection($data);
    }

    /**
     * Store SP2D baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'TAHUN' => 'required|string|max:4',
            'ID_USER' => 'required|integer',
            'NAMA_USER' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:5',
            'KD_OPD2' => 'nullable|string|max:5',
            'KD_OPD3' => 'nullable|string|max:5',
            'KD_OPD4' => 'nullable|string|max:5',
            'KD_OPD5' => 'nullable|string|max:5',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'FILE_TTE' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'KODE_FILE' => 'nullable|string|max:255',
            'DITERIMA' => 'nullable|date',
            'DITOLAK' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:255',
            'URUSAN' => 'nullable|string|max:255',
            'KD_REF1' => 'nullable|string|max:5',
            'KD_REF2' => 'nullable|string|max:5',
            'KD_REF3' => 'nullable|string|max:5',
            'KD_REF4' => 'nullable|string|max:5',
            'KD_REF5' => 'nullable|string|max:5',
            'KD_REF6' => 'nullable|string|max:5',
            'NO_SPM' => 'nullable|string|max:50',
            'JENIS_BERKAS' => 'nullable|string|max:50',
            'ID_BERKAS' => 'nullable|integer',
            'AGREEMENT' => 'nullable|string|max:50',
            'KD_BELANJA1' => 'nullable|string|max:5',
            'KD_BELANJA2' => 'nullable|string|max:5',
            'KD_BELANJA3' => 'nullable|string|max:5',
            'JENIS_BELANJA' => 'nullable|string|max:50',
            'NILAI_BELANJA' => 'nullable|numeric',
            'STATUS_LAPORAN' => 'nullable|string|max:50',
        ]);

        try {
            // Ambil ID dari sequence Oracle (jika ada)
            $id = DB::connection('oracle')->selectOne('SELECT NO_SP2D.NEXTVAL AS ID FROM dual')->ID;

            $sp2d = SP2DModel::create(array_merge($validated, [
                'ID_SP2D' => $id,
                'created_at' => now(),
            ]));

            return new SP2DResource($sp2d);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D
     */
    public function show($id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DResource($sp2d);
    }

    /**
     * Update SP2D
     */
    public function update(Request $request, $id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'TAHUN' => 'required|string|max:4',
            'NAMA_USER' => 'required|string|max:255',
            'ID_OPERATOR' => 'nullable|integer',
            'NAMA_OPERATOR' => 'nullable|string|max:255',
            'NAMA_FILE' => 'required|string|max:255',
            'NAMA_FILE_ASLI' => 'nullable|string|max:255',
            'FILE_TTE' => 'nullable|string|max:255',
            'TANGGAL_UPLOAD' => 'nullable|date',
            'ALASAN_TOLAK' => 'nullable|string|max:500',
            'PROSES' => 'nullable|string|max:50',
            'SUPERVISOR_PROSES' => 'nullable|string|max:255',
            'URUSAN' => 'nullable|string|max:255',
            'STATUS_LAPORAN' => 'nullable|string|max:50',
        ]);

        try {
            $sp2d->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SP2DResource($sp2d),
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
     * Soft delete SP2D
     */
    public function destroy($id)
    {
        $sp2d = SP2DModel::where('ID_SP2D', $id)
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
