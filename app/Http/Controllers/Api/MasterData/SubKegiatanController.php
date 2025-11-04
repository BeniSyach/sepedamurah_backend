<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\SubKegiatanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SubKegiatanResource;

class SubKegiatanController extends Controller
{
    /**
     * Tampilkan daftar Sub Kegiatan (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = SubKegiatanModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_subkegiatan', 'like', "%{$search}%")
                ->orWhere('kd_subkeg1', 'like', "%{$search}%")
                ->orWhere('kd_subkeg2', 'like', "%{$search}%")
                ->orWhere('kd_subkeg3', 'like', "%{$search}%")
                ->orWhere('kd_subkeg4', 'like', "%{$search}%")
                ->orWhere('kd_subkeg5', 'like', "%{$search}%")
                ->orWhere('kd_subkeg6', 'like', "%{$search}%");
        }

        $data = $query->paginate(10);

        return SubKegiatanResource::collection($data);
    }

    /**
     * Simpan data Sub Kegiatan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_subkeg1'     => 'required|string|max:10',
            'kd_subkeg2'     => 'required|string|max:10',
            'kd_subkeg3'     => 'required|string|max:10',
            'kd_subkeg4'     => 'required|string|max:10',
            'kd_subkeg5'     => 'required|string|max:10',
            'kd_subkeg6'     => 'required|string|max:10',
            'nm_subkegiatan' => 'required|string|max:255',
        ]);

        try {
            $subkegiatan = SubKegiatanModel::create($validated);

            return new SubKegiatanResource($subkegiatan);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode sub kegiatan sudah terdaftar.',
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
     * Tampilkan detail Sub Kegiatan.
     */
    public function show($kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $subkegiatan = DB::connection('oracle')
                ->table(DB::raw('REF_SUBKEGIATAN'))
                ->whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$subkegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $subkegiatan,
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
     * Update data Sub Kegiatan.
     */
    public function update(Request $request, $kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $subkegiatan = SubKegiatanModel::whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->first();

            if (!$subkegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_subkegiatan' => 'required|string|max:255',
            ]);

            $subkegiatan->nm_subkegiatan = $validated['nm_subkegiatan'];
            $subkegiatan->save();

            return response()->json([
                'status' => true,
                'message' => 'Data sub kegiatan berhasil diperbarui',
                'data' => new SubKegiatanResource($subkegiatan),
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
     * Soft delete data Sub Kegiatan.
     */
    public function destroy($kd_subkeg1, $kd_subkeg2, $kd_subkeg3, $kd_subkeg4, $kd_subkeg5, $kd_subkeg6)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_SUBKEGIATAN')
                ->whereRaw('TRIM(KD_SUBKEG1) = ?', [trim($kd_subkeg1)])
                ->whereRaw('TRIM(KD_SUBKEG2) = ?', [trim($kd_subkeg2)])
                ->whereRaw('TRIM(KD_SUBKEG3) = ?', [trim($kd_subkeg3)])
                ->whereRaw('TRIM(KD_SUBKEG4) = ?', [trim($kd_subkeg4)])
                ->whereRaw('TRIM(KD_SUBKEG5) = ?', [trim($kd_subkeg5)])
                ->whereRaw('TRIM(KD_SUBKEG6) = ?', [trim($kd_subkeg6)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data sub kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data sub kegiatan berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_sub_kegiatan_sp2d(Request $request)
    {
        // 🔐 Ambil user dari JWT (pastikan middleware auth:api aktif)
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'User tidak terautentikasi'], 401);
        }
    
        // 📥 Ambil kd_keg dari body request
        $kd_keg1 = $request->input('kd_keg1');
        $kd_keg2 = $request->input('kd_keg2');
        $kd_keg3 = $request->input('kd_keg3');
        $kd_keg4 = $request->input('kd_keg4');
        $kd_keg5 = $request->input('kd_keg5');
    
        if (!$kd_keg1 && !$kd_keg2 && !$kd_keg3 && !$kd_keg4 && !$kd_keg5) {
            return response()->json(['error' => 'Parameter kd_keg wajib diisi'], 400);
        }
    
        // ⚙️ Bangun query Laravel
        $query = DB::table('REF_SUBKEGIATAN')
            ->distinct()
            ->select('REF_SUBKEGIATAN.*')
            ->join('PAGU_BELANJA', function ($join) {
                $join->on('REF_SUBKEGIATAN.KD_SUBKEG1', '=', 'PAGU_BELANJA.KD_SUBKEG1')
                     ->on('REF_SUBKEGIATAN.KD_SUBKEG2', '=', 'PAGU_BELANJA.KD_SUBKEG2')
                     ->on('REF_SUBKEGIATAN.KD_SUBKEG3', '=', 'PAGU_BELANJA.KD_SUBKEG3')
                     ->on('REF_SUBKEGIATAN.KD_SUBKEG4', '=', 'PAGU_BELANJA.KD_SUBKEG4')
                     ->on('REF_SUBKEGIATAN.KD_SUBKEG5', '=', 'PAGU_BELANJA.KD_SUBKEG5')
                     ->on('REF_SUBKEGIATAN.KD_SUBKEG6', '=', 'PAGU_BELANJA.KD_SUBKEG6');
            })
            ->join('REF_OPD', function ($join) {
                $join->on(DB::raw("
                    LOWER(REPLACE(COALESCE(REF_OPD.KODE_OPD, ''), ' ', ''))
                "), '=', DB::raw("
                    LOWER(REPLACE(
                        COALESCE(PAGU_BELANJA.KD_OPD1, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD2, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD3, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD4, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD5, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD6, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD7, '') || '.' ||
                        COALESCE(PAGU_BELANJA.KD_OPD8, '')
                    , ' ', ''))
                "));
            })
            ->where('REF_OPD.KD_OPD1', $user->kd_opd1)
            ->where('REF_OPD.KD_OPD2', $user->kd_opd2)
            ->where('REF_OPD.KD_OPD3', $user->kd_opd3)
            ->where('REF_OPD.KD_OPD4', $user->kd_opd4)
            ->where('REF_OPD.KD_OPD5', $user->kd_opd5)
            ->where('REF_OPD.HIDDEN', 0)
            ->where('PAGU_BELANJA.KD_KEG1', $kd_keg1)
            ->where('PAGU_BELANJA.KD_KEG2', $kd_keg2)
            ->where('PAGU_BELANJA.KD_KEG3', $kd_keg3)
            ->where('PAGU_BELANJA.KD_KEG4', $kd_keg4)
            ->where('PAGU_BELANJA.KD_KEG5', $kd_keg5)
            ->where('PAGU_BELANJA.IS_DELETED', 0)
            ->get();
    
            return response()->json([
                'data' => $query
            ]);
    }
}
