<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\KegiatanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\KegiatanResource;

class KegiatanController extends Controller
{
    /**
     * Tampilkan daftar Kegiatan (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = KegiatanModel::query();
    
        if ($search = $request->get('search')) {
    
            $searchLower = strtolower(trim($search));
    
            // Jika input mengandung titik, berarti kode berjenjang
            if (str_contains($searchLower, '.')) {
    
                $parts = explode('.', $searchLower);
    
                // Normalisasi format sesuai panjang kolom
                $kd1 = isset($parts[0]) ? substr($parts[0], 0, 1) : null;
                $kd2 = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : null;
                $kd3 = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : null;
                $kd4 = isset($parts[3]) ? substr($parts[3], 0, 1) : null;
                $kd5 = isset($parts[4]) ? str_pad($parts[4], 2, '0', STR_PAD_LEFT) : null;
    
                $query->where(function ($q) use ($kd1, $kd2, $kd3, $kd4, $kd5) {
                    if ($kd1) $q->whereRaw("TRIM(KD_KEG1) = ?", [$kd1]);
                    if ($kd2) $q->whereRaw("TRIM(KD_KEG2) = ?", [$kd2]);
                    if ($kd3) $q->whereRaw("TRIM(KD_KEG3) = ?", [$kd3]);
                    if ($kd4) $q->whereRaw("TRIM(KD_KEG4) = ?", [$kd4]);
                    if ($kd5) $q->whereRaw("TRIM(KD_KEG5) = ?", [$kd5]);
                });
    
            } else {
                // Jika input tanpa titik → cari nama dan sebagian kode
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw("LOWER(nm_kegiatan) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_KEG1)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_KEG2)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_KEG3)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_KEG4)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_KEG5)) LIKE ?", ["%{$searchLower}%"]);
                });
            }
        }
    
        $data = $query->paginate(10);
        return KegiatanResource::collection($data);
    }
    

    /**
     * Simpan data Kegiatan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_keg1'     => 'required|string|max:10',
            'kd_keg2'     => 'required|string|max:10',
            'kd_keg3'     => 'required|string|max:10',
            'kd_keg4'     => 'required|string|max:10',
            'kd_keg5'     => 'required|string|max:10',
            'nm_kegiatan' => 'required|string|max:255',
        ]);

        try {
            $kegiatan = KegiatanModel::create($validated);

            return new KegiatanResource($kegiatan);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode kegiatan sudah terdaftar.',
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
     * Tampilkan detail Kegiatan.
     */
    public function show($kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $kegiatan = DB::connection('oracle')
                ->table(DB::raw('REF_KEGIATAN'))
                ->whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$kegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $kegiatan,
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
     * Update data Kegiatan.
     */
    public function update(Request $request, $kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $kegiatan = KegiatanModel::whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->first();

            if (!$kegiatan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_kegiatan' => 'required|string|max:255',
            ]);

            $kegiatan->nm_kegiatan = $validated['nm_kegiatan'];
            $kegiatan->save();

            return response()->json([
                'status' => true,
                'message' => 'Data kegiatan berhasil diperbarui',
                'data' => new KegiatanResource($kegiatan),
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
     * Soft delete data Kegiatan.
     */
    public function destroy($kd_keg1, $kd_keg2, $kd_keg3, $kd_keg4, $kd_keg5)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_KEGIATAN')
                ->whereRaw('TRIM(KD_KEG1) = ?', [trim($kd_keg1)])
                ->whereRaw('TRIM(KD_KEG2) = ?', [trim($kd_keg2)])
                ->whereRaw('TRIM(KD_KEG3) = ?', [trim($kd_keg3)])
                ->whereRaw('TRIM(KD_KEG4) = ?', [trim($kd_keg4)])
                ->whereRaw('TRIM(KD_KEG5) = ?', [trim($kd_keg5)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kegiatan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data kegiatan berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_kegiatan_sp2d(Request $request)
    {
        // 🔐 Ambil user dari JWT token
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'User tidak terautentikasi'], 401);
        }
    
        // 📥 Ambil kd_prog dari body request
        $kd_prog1 = $request->input('kd_prog1');
        $kd_prog2 = $request->input('kd_prog2');
        $kd_prog3 = $request->input('kd_prog3');
    
        if (!$kd_prog1 && !$kd_prog2 && !$kd_prog3) {
            return response()->json(['error' => 'Parameter kd_prog wajib diisi'], 400);
        }
    
        // 🧱 Query builder
        $query = DB::table('REF_KEGIATAN')
            ->distinct()
            ->select('REF_KEGIATAN.*')
            ->join('PAGU_BELANJA', function ($join) {
                $join->on('REF_KEGIATAN.KD_KEG1', '=', 'PAGU_BELANJA.KD_KEG1')
                     ->on('REF_KEGIATAN.KD_KEG2', '=', 'PAGU_BELANJA.KD_KEG2')
                     ->on('REF_KEGIATAN.KD_KEG3', '=', 'PAGU_BELANJA.KD_KEG3')
                     ->on('REF_KEGIATAN.KD_KEG4', '=', 'PAGU_BELANJA.KD_KEG4')
                     ->on('REF_KEGIATAN.KD_KEG5', '=', 'PAGU_BELANJA.KD_KEG5');
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
            ->where('PAGU_BELANJA.KD_KEG1', $kd_prog1)
            ->where('PAGU_BELANJA.KD_KEG2', $kd_prog2)
            ->where('PAGU_BELANJA.KD_KEG3', $kd_prog3)
            ->where('PAGU_BELANJA.IS_DELETED', 0)
            ->get();
    
            return response()->json([
                'data' => $query
            ]);
    }
}
