<?php

namespace App\Http\Controllers\Api\AlokasiDana;

use App\Http\Controllers\Controller;
use App\Models\BesaranUPSKPDModel;
use Illuminate\Http\Request;
use App\Http\Resources\BesaranUPSKPDResource;
use Illuminate\Support\Facades\DB;

class BesaranUPSKPDController extends Controller
{
    /**
     * Tampilkan daftar Besaran UP SKPD (pagination + search)
     */
    public function index(Request $request)
    {
        $query = BesaranUPSKPDModel::whereNull('pagu_up.deleted_at')
                ->join('ref_opd', function ($join) {
                    $join->on('pagu_up.kd_opd1', '=', 'ref_opd.kd_opd1')
                        ->on('pagu_up.kd_opd2', '=', 'ref_opd.kd_opd2')
                        ->on('pagu_up.kd_opd3', '=', 'ref_opd.kd_opd3')
                        ->on('pagu_up.kd_opd4', '=', 'ref_opd.kd_opd4')
                        ->on('pagu_up.kd_opd5', '=', 'ref_opd.kd_opd5');
                });

        // 🔍 Filter pencarian jika ada parameter "search"
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('pagu_up.tahun', 'like', "%{$search}%");
                $q->orWhereRaw("LOWER(nm_opd) LIKE ?", ["%$search%"]);
            });
        }

            // 📄 Ambil data dengan pagination
            $data = $query->orderBy('id', 'asc')
            ->paginate($request->get('per_page', 10));

                // 🔗 Tambahkan relasi skpd secara manual
                $data->getCollection()->transform(function ($item) {
                    $skpd = $item->skpd(); // panggil accessor skpd() dari model
                    $item->setRelation('skpd', $skpd); // daftarkan relasi ke Eloquent
                    return $item;
                });

        return BesaranUPSKPDResource::collection($data);
    }

    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_opd1' => 'required|string|max:10',
            'kd_opd2' => 'required|string|max:10',
            'kd_opd3' => 'required|string|max:10',
            'kd_opd4' => 'required|string|max:10',
            'kd_opd5' => 'required|string|max:10',
            'tahun'   => 'required|integer',
            'pagu'    => 'nullable|numeric',
            'up_kkpd' => 'nullable|numeric',
        ]);

        try {
            $besaran = BesaranUPSKPDModel::create($validated);

            return new BesaranUPSKPDResource($besaran);

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
    public function show($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5, $tahun)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('PAGU_UP'))
                ->whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
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
    public function update(Request $request, $kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5, $tahun)
    {
        try {
            $besaran = BesaranUPSKPDModel::whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
                ->where('tahun', $tahun)
                ->first();

            if (!$besaran) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'pagu'    => 'nullable|numeric',
                'up_kkpd' => 'nullable|numeric',
            ]);

            $besaran->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new BesaranUPSKPDResource($besaran),
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
    public function destroy($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5, $tahun)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PAGU_UP')
                ->whereRaw('TRIM(KD_OPD1) = ?', [$kd_opd1])
                ->whereRaw('TRIM(KD_OPD2) = ?', [$kd_opd2])
                ->whereRaw('TRIM(KD_OPD3) = ?', [$kd_opd3])
                ->whereRaw('TRIM(KD_OPD4) = ?', [$kd_opd4])
                ->whereRaw('TRIM(KD_OPD5) = ?', [$kd_opd5])
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
