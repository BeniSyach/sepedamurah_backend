<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\JenisBelanjaModel;
use Illuminate\Http\Request;
use App\Http\Resources\JenisBelanjaResource;
use Illuminate\Support\Facades\DB;

class JenisBelanjaController extends Controller
{
    /**
     * Tampilkan daftar Jenis Belanja (pagination + search)
     */
    public function index(Request $request)
    {
        $query = JenisBelanjaModel::query();
    
        if ($search = $request->get('search')) {
    
            $searchLower = strtolower(trim($search));
    
            $parts = explode('.', $searchLower);
    
            // --- KODE FORMAT 1 BAGIAN ---
            if (count($parts) === 1 && preg_match('/^[0-9]+$/', $parts[0])) {
                $r1 = substr($parts[0], 0, 1);
                $query->whereRaw("RTRIM(kd_ref1) = ?", [$r1]);
            }
    
            // --- KODE FORMAT 2 BAGIAN ---
            elseif (count($parts) === 2) {
                [$r1, $r2] = $parts;
                $r1 = substr($r1, 0, 1);
                $r2 = substr($r2, 0, 1);
                $query->whereRaw("RTRIM(kd_ref1) = ?", [$r1])
                      ->whereRaw("RTRIM(kd_ref2) = ?", [$r2]);
            }
    
            // --- KODE FORMAT 3 BAGIAN ---
            elseif (count($parts) === 3) {
                [$r1, $r2, $r3] = $parts;
                $r1 = substr($r1, 0, 1);
                $r2 = substr($r2, 0, 1);
                $r3 = str_pad($r3, 2, '0', STR_PAD_LEFT);
                $query->whereRaw("RTRIM(kd_ref1) = ?", [$r1])
                      ->whereRaw("RTRIM(kd_ref2) = ?", [$r2])
                      ->whereRaw("RTRIM(kd_ref3) = ?", [$r3]);
            }
    
            // --- PENCARIAN NORMAL (nama) ---
            else {
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw("LOWER(nm_belanja) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(RTRIM(kd_ref1)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(RTRIM(kd_ref2)) LIKE ?", ["%{$searchLower}%"])
                      ->orWhereRaw("LOWER(RTRIM(kd_ref3)) LIKE ?", ["%{$searchLower}%"]);
                });
            }
        }
    
        $data = $query->orderBy('kd_ref1')
                      ->orderBy('kd_ref2')
                      ->orderBy('kd_ref3')
                      ->paginate($request->get('per_page', 10));
    
        return JenisBelanjaResource::collection($data);
    }
    

    /**
     * Simpan Jenis Belanja baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_ref1' => 'required|string|max:10',
            'kd_ref2' => 'required|string|max:10',
            'kd_ref3' => 'required|string|max:10',
            'nm_belanja' => 'required|string|max:255',
        ]);

        try {
            $jenis = JenisBelanjaModel::create($validated);

            return new JenisBelanjaResource($jenis);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis Belanja sudah terdaftar.',
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
     * Tampilkan detail Jenis Belanja
     */
    public function show($kd_ref1, $kd_ref2, $kd_ref3)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('REF_JENIS_BELANJA'))
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis Belanja tidak ditemukan',
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
     * Update data Jenis Belanja
     */
    public function update(Request $request, $kd_ref1, $kd_ref2, $kd_ref3)
    {
        try {
            $jenis = JenisBelanjaModel::whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                        ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                        ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                        ->first();

            if (!$jenis) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis Belanja tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_belanja' => 'required|string|max:255',
            ]);

            $jenis->nm_belanja = $validated['nm_belanja'];
            $jenis->save();

            return response()->json([
                'status' => true,
                'message' => 'Data Jenis Belanja berhasil diperbarui',
                'data' => new JenisBelanjaResource($jenis),
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
     * Soft delete data Jenis Belanja
     */
    public function destroy($kd_ref1, $kd_ref2, $kd_ref3)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_JENIS_BELANJA')
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->update(['deleted_at' => now()]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Jenis Belanja tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Jenis Belanja berhasil dihapus (soft delete)',
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
