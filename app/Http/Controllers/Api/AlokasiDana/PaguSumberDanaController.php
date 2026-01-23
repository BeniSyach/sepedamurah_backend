<?php

namespace App\Http\Controllers\Api\AlokasiDana;

use App\Http\Controllers\Controller;
use App\Models\PaguSumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\PaguSumberDanaResource;
use Illuminate\Support\Facades\DB;

class PaguSumberDanaController extends Controller
{
    /**
     * Tampilkan daftar Pagu Sumber Dana (pagination + search)
     */
    public function index(Request $request)
    {
        $query = PaguSumberDanaModel::query()->whereNull('pagu_sumber_dana.deleted_at')
                ->join('ref_sumber_dana', function ($join) {
                    $join->on('pagu_sumber_dana.kd_ref1', '=', 'ref_sumber_dana.kd_ref1')
                        ->on('pagu_sumber_dana.kd_ref2', '=', 'ref_sumber_dana.kd_ref2')
                        ->on('pagu_sumber_dana.kd_ref3', '=', 'ref_sumber_dana.kd_ref3')
                        ->on('pagu_sumber_dana.kd_ref4', '=', 'ref_sumber_dana.kd_ref4')
                        ->on('pagu_sumber_dana.kd_ref5', '=', 'ref_sumber_dana.kd_ref5')
                        ->on('pagu_sumber_dana.kd_ref6', '=', 'ref_sumber_dana.kd_ref6');
                });

        // 🔍 Filter pencarian
        if ($search = $request->get('search')) {
            $search = strtolower($search);
        
            $query->where(function ($q) use ($search) {
                $q->orWhereRaw("LOWER(pagu) LIKE ?", ["%{$search}%"]);
                $q->orWhereRaw("LOWER(jumlah_silpa) LIKE ?", ["%{$search}%"]);
                $q->orWhereRaw("LOWER(nm_ref) LIKE ?", ["%{$search}%"]);
            });
        }
        

        // 🔍 Filter tahun jika ada parameter "tahun"
        if ($tahun = $request->get('tahun')) {
            $query->where('pagu_sumber_dana.tahun', $tahun);
        }
    
        // 🔢 Pagination dan sorting
        $data = $query->orderByDesc('tahun')->paginate($request->get('per_page', 10));
    
        // 🧾 Kembalikan hasil menggunakan Resource
        return PaguSumberDanaResource::collection($data);
    }

    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_ref1'      => 'required|string|max:10',
            'kd_ref2'      => 'required|string|max:10',
            'kd_ref3'      => 'required|string|max:10',
            'kd_ref4'      => 'required|string|max:10',
            'kd_ref5'      => 'required|string|max:10',
            'kd_ref6'      => 'required|string|max:10',
            'tahun'        => 'required|integer',
            'tgl_rekam'    => 'nullable|date',
            'pagu'         => 'nullable|numeric',
            'jumlah_silpa' => 'nullable|numeric',
        ]);

        try {
            $pagu = PaguSumberDanaModel::create($validated);
            return new PaguSumberDanaResource($pagu);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sudah ada.',
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
     * Tampilkan detail data
     */
    public function show($kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6, $tahun)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PAGU_SUMBER_DANA'))
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->where('tahun', $tahun)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
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
     * Update data
     */
    public function update(Request $request, $kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6, $tahun)
    {
        try {
            $pagu = PaguSumberDanaModel::whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->where('tahun', $tahun)
                ->first();

            if (!$pagu) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'tgl_rekam'    => 'nullable|date',
                'pagu'         => 'nullable|numeric',
                'jumlah_silpa' => 'nullable|numeric',
            ]);

            $pagu->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new PaguSumberDanaResource($pagu),
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
     * Soft delete
     */
    public function destroy($kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6, $tahun)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PAGU_SUMBER_DANA')
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->where('tahun', $tahun)
                ->update(['deleted_at' => now()]);

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
