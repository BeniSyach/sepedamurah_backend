<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\SumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\SumberDanaResource;
use Illuminate\Support\Facades\DB;

class SumberDanaController extends Controller
{
    /**
     * Tampilkan daftar Sumber Dana (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SumberDanaModel::query();
    
        if ($search = $request->get('search')) {
    
            $searchLower = strtolower(trim($search));
            $parts = explode('.', $searchLower);
    
            // Filter berdasarkan jumlah level
            if (count($parts) > 0 && array_reduce($parts, fn($carry, $item) => $carry && preg_match('/^[0-9]+$/', $item), true)) {
                $query->where(function($q) use ($parts) {
                    $levelColumns = ['kd_ref1','kd_ref2','kd_ref3','kd_ref4','kd_ref5','kd_ref6'];
    
                    foreach ($parts as $i => $part) {
                        if (!isset($levelColumns[$i])) continue;
    
                        $col = $levelColumns[$i];
    
                        // Panjang kolom sesuai tabel
                        if ($i === 0 || $i === 1) $part = substr($part, 0, 1);        // KD_REF1, KD_REF2 = 1 digit
                        elseif ($i >= 2 && $i <= 4) $part = str_pad($part, 2, '0', STR_PAD_LEFT); // KD_REF3-5 = 2 digit
                        elseif ($i === 5) $part = str_pad($part, 4, '0', STR_PAD_LEFT); // KD_REF6 = 4 digit
    
                        $q->whereRaw("RTRIM({$col}) = ?", [$part]);
                    }
                });
            } else {
                // Pencarian normal case-insensitive
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw("LOWER(nm_ref) LIKE ?", ["%{$searchLower}%"]);
                    foreach (['kd_ref1','kd_ref2','kd_ref3','kd_ref4','kd_ref5','kd_ref6'] as $col) {
                        $q->orWhereRaw("LOWER(RTRIM({$col})) LIKE ?", ["%{$searchLower}%"]);
                    }
                });
            }
        }
    
        // Filter status jika ada
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
    
        $data = $query->orderBy('kd_ref1')
                      ->orderBy('kd_ref2')
                      ->orderBy('kd_ref3')
                      ->orderBy('kd_ref4')
                      ->orderBy('kd_ref5')
                      ->orderBy('kd_ref6')
                      ->paginate($request->get('per_page', 10));
    
        return SumberDanaResource::collection($data);
    }    

    /**
     * Simpan Sumber Dana baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_ref1' => 'required|string|max:10',
            'kd_ref2' => 'required|string|max:10',
            'kd_ref3' => 'required|string|max:10',
            'kd_ref4' => 'required|string|max:10',
            'kd_ref5' => 'required|string|max:10',
            'kd_ref6' => 'required|string|max:10',
            'nm_ref' => 'required|string|max:255',
            'status' => 'nullable|integer',
            'jenis_sumber_dana' => 'required|string|max:255',
        ]);

        try {
            $sumberDana = SumberDanaModel::create($validated);

            return new SumberDanaResource($sumberDana);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Sumber Dana sudah terdaftar.',
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
     * Tampilkan detail Sumber Dana
     */
    public function show($kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6)
    {
        try {
            $data = DB::connection('oracle')
                ->table(DB::raw('REF_SUMBER_DANA'))
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Sumber Dana tidak ditemukan',
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
     * Update data Sumber Dana
     */
    public function update(Request $request, $kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6)
    {
        try {
            $sumberDana = SumberDanaModel::whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->first();

            if (!$sumberDana) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Sumber Dana tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nm_ref' => 'required|string|max:255',
                'status' => 'nullable|integer',
            ]);

            $sumberDana->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data Sumber Dana berhasil diperbarui',
                'data' => new SumberDanaResource($sumberDana),
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
     * Soft delete data Sumber Dana
     */
    public function destroy($kd_ref1, $kd_ref2, $kd_ref3, $kd_ref4, $kd_ref5, $kd_ref6)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('REF_SUMBER_DANA')
                ->whereRaw('TRIM(KD_REF1) = ?', [$kd_ref1])
                ->whereRaw('TRIM(KD_REF2) = ?', [$kd_ref2])
                ->whereRaw('TRIM(KD_REF3) = ?', [$kd_ref3])
                ->whereRaw('TRIM(KD_REF4) = ?', [$kd_ref4])
                ->whereRaw('TRIM(KD_REF5) = ?', [$kd_ref5])
                ->whereRaw('TRIM(KD_REF6) = ?', [$kd_ref6])
                ->update(['deleted_at' => now()]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Sumber Dana tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Sumber Dana berhasil dihapus (soft delete)',
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
