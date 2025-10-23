<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengembalianModel;
use Illuminate\Http\Request;
use App\Http\Resources\PengembalianResource;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    /**
     * Daftar data pengembalian dengan pagination dan search.
     */
    public function index(Request $request)
    {
        $query = PengembalianModel::query();

        if ($search = $request->get('search')) {
            $query->where('NAMA', 'like', "%{$search}%")
                  ->orWhere('NIK', 'like', "%{$search}%")
                  ->orWhere('NO_STS', 'like', "%{$search}%");
        }

        $data = $query->orderBy('TGL_REKAM', 'desc')
                      ->paginate($request->get('per_page', 10));

        return PengembalianResource::collection($data);
    }

    /**
     * Simpan data baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'NO_STS' => 'required|string|unique:DATA_PENGEMBALIAN,NO_STS',
            'NIK' => 'required|string|max:50',
            'NAMA' => 'required|string|max:255',
            'ALAMAT' => 'nullable|string|max:255',
            'TAHUN' => 'required|string|max:4',
            'KD_REK1' => 'nullable|string|max:2',
            'KD_REK2' => 'nullable|string|max:2',
            'KD_REK3' => 'nullable|string|max:2',
            'KD_REK4' => 'nullable|string|max:2',
            'KD_REK5' => 'nullable|string|max:2',
            'KD_REK6' => 'nullable|string|max:4',
            'NM_REKENING' => 'required|string|max:100',
            'KETERANGAN' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:2',
            'KD_OPD2' => 'nullable|string|max:2',
            'KD_OPD3' => 'nullable|string|max:2',
            'KD_OPD4' => 'nullable|string|max:2',
            'KD_OPD5' => 'nullable|string|max:2',
            'JML_PENGEMBALIAN' => 'nullable|numeric',
            'TGL_REKAM' => 'nullable|date',
            'JML_YG_DISETOR' => 'nullable|numeric',
            'TGL_SETOR' => 'nullable|date',
            'NIP_PEREKAM' => 'nullable|string|max:50',
            'KODE_PENGESAHAN' => 'nullable|string|max:10',
            'KODE_CABANG' => 'nullable|string|max:10',
            'NAMA_CHANNEL' => 'nullable|string|max:50',
            'STATUS_PEMBAYARAN_PAJAK' => 'nullable|string|max:50',
        ]);

        try {
            $pengembalian = PengembalianModel::create($validated);
            return new PengembalianResource($pengembalian);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tampilkan detail 
     */
    public function show($id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new PengembalianResource($pengembalian);
    }

    /**
     * Update data 
     */
    public function update(Request $request, $id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'NIK' => 'required|string|max:50',
            'NAMA' => 'required|string|max:255',
            'ALAMAT' => 'nullable|string|max:255',
            'TAHUN' => 'required|string|max:4',
            'KD_REK1' => 'nullable|string|max:2',
            'KD_REK2' => 'nullable|string|max:2',
            'KD_REK3' => 'nullable|string|max:2',
            'KD_REK4' => 'nullable|string|max:2',
            'KD_REK5' => 'nullable|string|max:2',
            'KD_REK6' => 'nullable|string|max:4',
            'NM_REKENING' => 'required|string|max:100',
            'KETERANGAN' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:2',
            'KD_OPD2' => 'nullable|string|max:2',
            'KD_OPD3' => 'nullable|string|max:2',
            'KD_OPD4' => 'nullable|string|max:2',
            'KD_OPD5' => 'nullable|string|max:2',
            'JML_PENGEMBALIAN' => 'nullable|numeric',
            'TGL_REKAM' => 'nullable|date',
            'JML_YG_DISETOR' => 'nullable|numeric',
            'TGL_SETOR' => 'nullable|date',
            'NIP_PEREKAM' => 'nullable|string|max:50',
            'KODE_PENGESAHAN' => 'nullable|string|max:10',
            'KODE_CABANG' => 'nullable|string|max:10',
            'NAMA_CHANNEL' => 'nullable|string|max:50',
            'STATUS_PEMBAYARAN_PAJAK' => 'nullable|string|max:50',
        ]);

        $pengembalian->update($validated);

        return new PengembalianResource($pengembalian);
    }

    /**
     * Soft delete data 
     */
    public function destroy($id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $pengembalian->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }
}
