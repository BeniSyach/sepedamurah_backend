<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RekeningModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RekeningResource;

class RekeningController extends Controller
{
    /**
     * Tampilkan daftar Rekening (dengan pagination & pencarian).
     */
    public function index(Request $request)
    {
        $query = RekeningModel::query();
    
        if ($search = $request->get('search')) {
    
            $searchLower = strtolower(trim($search));
    
            if (str_contains($searchLower, '.')) {
    
                $parts = explode('.', $searchLower);
    
                // Normalisasi panjang sesuai tabel
                $kd1 = isset($parts[0]) ? substr($parts[0], 0, 1) : null;
                $kd2 = isset($parts[1]) ? substr($parts[1], 0, 1) : null;
                $kd3 = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : null;
                $kd4 = isset($parts[3]) ? str_pad($parts[3], 2, '0', STR_PAD_LEFT) : null;
                $kd5 = isset($parts[4]) ? str_pad($parts[4], 2, '0', STR_PAD_LEFT) : null;
                $kd6 = isset($parts[5]) ? str_pad($parts[5], 4, '0', STR_PAD_LEFT) : null;
    
                $query->where(function ($q) use ($kd1, $kd2, $kd3, $kd4, $kd5, $kd6) {
                    if ($kd1) $q->whereRaw("TRIM(KD_REKENING1) = ?", [$kd1]);
                    if ($kd2) $q->whereRaw("TRIM(KD_REKENING2) = ?", [$kd2]);
                    if ($kd3) $q->whereRaw("TRIM(KD_REKENING3) = ?", [$kd3]);
                    if ($kd4) $q->whereRaw("TRIM(KD_REKENING4) = ?", [$kd4]);
                    if ($kd5) $q->whereRaw("TRIM(KD_REKENING5) = ?", [$kd5]);
                    if ($kd6) $q->whereRaw("TRIM(KD_REKENING6) = ?", [$kd6]);
                });
    
            } else {
                // Jika input tanpa titik → cari nama dan sebagian kode
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw("LOWER(NM_REKENING) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING1)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING2)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING3)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING4)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING5)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(TRIM(KD_REKENING6)) LIKE ?", ["%{$searchLower}%"]);
                });
            }
        }
    
        return RekeningResource::collection(
            $query->paginate(10)
        );
    }    

    /**
     * Simpan data Rekening baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_rekening1' => 'required|string|max:10',
            'kd_rekening2' => 'required|string|max:10',
            'kd_rekening3' => 'required|string|max:10',
            'kd_rekening4' => 'required|string|max:10',
            'kd_rekening5' => 'required|string|max:10',
            'kd_rekening6' => 'required|string|max:10',
            'nm_rekening'  => 'required|string|max:255',
        ]);

        try {
            $rekening = RekeningModel::create($validated);

            return new RekeningResource($rekening);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode rekening sudah terdaftar.',
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
     * Tampilkan detail Rekening.
     */
    public function show($kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $rekening = DB::connection('oracle')
                ->table(DB::raw('REF_REKENING'))
                ->whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->whereNull('DELETED_AT')
                ->first();

            if (!$rekening) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $rekening,
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
     * Update data Rekening.
     */
    public function update(Request $request, $kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $rekening = RekeningModel::whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->first();

            if (!$rekening) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_rekening' => 'required|string|max:255',
            ]);

            $rekening->nm_rekening = $validated['nm_rekening'];
            $rekening->save();

            return response()->json([
                'status' => true,
                'message' => 'Data rekening berhasil diperbarui',
                'data' => new RekeningResource($rekening),
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
     * Soft delete data Rekening.
     */
    public function destroy($kd_rekening1, $kd_rekening2, $kd_rekening3, $kd_rekening4, $kd_rekening5, $kd_rekening6)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_REKENING')
                ->whereRaw('TRIM(KD_REKENING1) = ?', [trim($kd_rekening1)])
                ->whereRaw('TRIM(KD_REKENING2) = ?', [trim($kd_rekening2)])
                ->whereRaw('TRIM(KD_REKENING3) = ?', [trim($kd_rekening3)])
                ->whereRaw('TRIM(KD_REKENING4) = ?', [trim($kd_rekening4)])
                ->whereRaw('TRIM(KD_REKENING5) = ?', [trim($kd_rekening5)])
                ->whereRaw('TRIM(KD_REKENING6) = ?', [trim($kd_rekening6)])
                ->update([
                    'DELETED_AT' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rekening tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data rekening berhasil dihapus (soft delete)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_rekening_sp2d(Request $request)
    {
        // 🔐 Ambil user yang sedang login (pastikan middleware auth:api aktif)
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['error' => 'User tidak terautentikasi'], 401);
        }
    
        // 📥 Ambil kd_subkeg dari request
        $kd_subkeg1 = $request->input('kd_subkeg1');
        $kd_subkeg2 = $request->input('kd_subkeg2');
        $kd_subkeg3 = $request->input('kd_subkeg3');
        $kd_subkeg4 = $request->input('kd_subkeg4');
        $kd_subkeg5 = $request->input('kd_subkeg5');
        $kd_subkeg6 = $request->input('kd_subkeg6');
    
        if (!$kd_subkeg1) {
            return response()->json(['error' => 'Parameter kd_subkeg wajib diisi'], 400);
        }
    
        // ⚙️ Bangun query utama
        $query = DB::table('REF_REKENING')
            ->distinct()
            ->select('REF_REKENING.*')
            ->join('PAGU_BELANJA', function ($join) {
                $join->on(DB::raw("TRIM(REF_REKENING.KD_REKENING1)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING1)"))
                ->on(DB::raw("TRIM(REF_REKENING.KD_REKENING2)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING2)"))
                ->on(DB::raw("TRIM(REF_REKENING.KD_REKENING3)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING3)"))
                ->on(DB::raw("TRIM(REF_REKENING.KD_REKENING4)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING4)"))
                ->on(DB::raw("TRIM(REF_REKENING.KD_REKENING5)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING5)"))
                ->on(DB::raw("TRIM(REF_REKENING.KD_REKENING6)"), '=', DB::raw("TRIM(PAGU_BELANJA.KD_REKENING6)"));           
            })
            ->join('REF_OPD', function ($join) {
                // Jika pakai PostgreSQL
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
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG1)"), trim($kd_subkeg1))
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG2)"), trim($kd_subkeg2))
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG3)"), trim($kd_subkeg3))
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG4)"), trim($kd_subkeg4))
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG5)"), trim($kd_subkeg5))
            ->where(DB::raw("TRIM(PAGU_BELANJA.KD_SUBKEG6)"), trim($kd_subkeg6))
            
            // ->where('PAGU_BELANJA.IS_DELETED', 0)
            ->get();
    
        // 🔄 Kembalikan hasil JSON
        return response()->json([
            'data' => $query
        ]);
    }
}
