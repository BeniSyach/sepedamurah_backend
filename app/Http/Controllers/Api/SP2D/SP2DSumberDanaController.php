<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DSumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DSumberDanaResource;
use Illuminate\Support\Facades\DB;

class SP2DSumberDanaController extends Controller
{
    /**
     * List SP2D Sumber Dana (pagination + search)
     */
    public function index(Request $request)
    {
        $query = SP2DSumberDanaModel::query()->whereNull('DELETED_AT');

        if ($search = $request->get('search')) {
            $query->where('SP2D_ID', 'like', "%{$search}%")
                  ->orWhere('KD_REF1', 'like', "%{$search}%")
                  ->orWhere('KD_REF2', 'like', "%{$search}%");
        }

        $data = $query->orderBy('SP2D_ID', 'asc')
                      ->paginate($request->get('per_page', 10));

        return SP2DSumberDanaResource::collection($data);
    }

    /**
     * Store SP2D Sumber Dana baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'SP2D_ID' => 'required|integer',
            'KD_REF1' => 'required|string|max:1',
            'KD_REF2' => 'required|string|max:1',
            'KD_REF3' => 'nullable|string|max:2',
            'KD_REF4' => 'nullable|string|max:2',
            'KD_REF5' => 'nullable|string|max:2',
            'KD_REF6' => 'nullable|string|max:4',
            'NILAI' => 'nullable|numeric',
        ]);

        try {
            $sumber = SP2DSumberDanaModel::create(array_merge($validated, [
                'CREATED_AT' => now(),
            ]));

            return new SP2DSumberDanaResource($sumber);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail SP2D Sumber Dana
     */
    public function show($id)
    {
        $sumber = SP2DSumberDanaModel::where('ID', $id)
                                     ->whereNull('DELETED_AT')
                                     ->first();

        if (!$sumber) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DSumberDanaResource($sumber);
    }

    /**
     * Update SP2D Sumber Dana
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'sumber_dana' => 'required|json',
        ]);
    
        $sp2dId = $request->id;
        $sumberDanaList = json_decode($request->sumber_dana, true);
    
        if (!is_array($sumberDanaList) || count($sumberDanaList) === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Sumber dana tidak valid',
            ], 422);
        }
    
        DB::beginTransaction();
    
        try {
            /** 🔥 Soft delete sumber dana lama */
            SP2DSumberDanaModel::where('sp2d_id', $sp2dId)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => now(),
                ]);
    
            /** 🔥 Insert ulang sumber dana */
            foreach ($sumberDanaList as $item) {
                validator($item, [
                    'kd_ref1' => 'required|string|max:1',
                    'kd_ref2' => 'required|string|max:1',
                    'kd_ref3' => 'nullable|string|max:2',
                    'kd_ref4' => 'nullable|string|max:2',
                    'kd_ref5' => 'nullable|string|max:2',
                    'kd_ref6' => 'nullable|string|max:4',
                    'nilai'   => 'required|numeric|min:0',
                ])->validate();
    
                SP2DSumberDanaModel::create([
                    'sp2d_id' => $sp2dId,
                    'kd_ref1' => $item['kd_ref1'],
                    'kd_ref2' => $item['kd_ref2'],
                    'kd_ref3' => $item['kd_ref3'] ?? null,
                    'kd_ref4' => $item['kd_ref4'] ?? null,
                    'kd_ref5' => $item['kd_ref5'] ?? null,
                    'kd_ref6' => $item['kd_ref6'] ?? null,
                    'nilai'   => $item['nilai'],
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'message' => 'Sumber dana berhasil diperbarui',
            ]);
    
        } catch (\Throwable $e) {
            DB::rollBack();
    
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui sumber dana',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete SP2D Sumber Dana
     */
    public function destroy($id)
    {
        $sumber = SP2DSumberDanaModel::where('ID', $id)
                                     ->whereNull('DELETED_AT')
                                     ->first();

        if (!$sumber) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $sumber->DELETED_AT = now();
        $sumber->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }

    public function check_sd(Request $request)
    {
        try {
            // Ambil tahun dari request, default ke tahun saat ini
            $tahun = $request->get('tahun', date('Y'));
    
            $sql = "
                SELECT 
                    COALESCE(a.kd_ref1, b.kd_ref1, c.kd_ref1) AS kd_ref1, 
                    COALESCE(a.kd_ref2, b.kd_ref2, c.kd_ref2) AS kd_ref2, 
                    COALESCE(a.kd_ref3, b.kd_ref3, c.kd_ref3) AS kd_ref3, 
                    COALESCE(a.kd_ref4, b.kd_ref4, c.kd_ref4) AS kd_ref4, 
                    COALESCE(a.kd_ref5, b.kd_ref5, c.kd_ref5) AS kd_ref5, 
                    COALESCE(a.kd_ref6, b.kd_ref6, c.kd_ref6) AS kd_ref6,
                    d.nm_ref AS nm_sumber,
                    d.jenis_sumber_dana,
                    NVL(a.pagu, 0) AS pagu,
                    NVL(a.jumlah_silpa, 0) AS jumlah_silpa,
                    NVL(b.jum_sumber_dana, 0) AS sumber_dana,
                    NVL(c.jum_belanja, 0) AS belanja,
                    NVL(a.jumlah_silpa, 0) + NVL(b.jum_sumber_dana, 0) - NVL(c.jum_belanja, 0) AS sisa
                FROM 
                    pagu_sumber_dana a
                FULL OUTER JOIN 
                    v_group_sumber_dana b
                ON 
                    NVL(a.kd_ref1, 'NULL') = NVL(b.kd_ref1, 'NULL')
                    AND NVL(a.kd_ref2, 'NULL') = NVL(b.kd_ref2, 'NULL')
                    AND NVL(a.kd_ref3, 'NULL') = NVL(b.kd_ref3, 'NULL')
                    AND NVL(a.kd_ref4, 'NULL') = NVL(b.kd_ref4, 'NULL')
                    AND NVL(a.kd_ref5, 'NULL') = NVL(b.kd_ref5, 'NULL')
                    AND NVL(a.kd_ref6, 'NULL') = NVL(b.kd_ref6, 'NULL')
                    AND a.tahun = b.tahun
                FULL OUTER JOIN 
                    v_group_sp2d_temp c
                ON 
                    NVL(a.kd_ref1, 'NULL') = NVL(c.kd_ref1, 'NULL')
                    AND NVL(a.kd_ref2, 'NULL') = NVL(c.kd_ref2, 'NULL')
                    AND NVL(a.kd_ref3, 'NULL') = NVL(c.kd_ref3, 'NULL')
                    AND NVL(a.kd_ref4, 'NULL') = NVL(c.kd_ref4, 'NULL')
                    AND NVL(a.kd_ref5, 'NULL') = NVL(c.kd_ref5, 'NULL')
                    AND NVL(a.kd_ref6, 'NULL') = NVL(c.kd_ref6, 'NULL')
                    AND a.tahun = c.tahun
                LEFT JOIN 
                    ref_sumber_dana d
                ON 
                    COALESCE(a.kd_ref1, b.kd_ref1, c.kd_ref1) = d.kd_ref1
                    AND COALESCE(a.kd_ref2, b.kd_ref2, c.kd_ref2) = d.kd_ref2
                    AND COALESCE(a.kd_ref3, b.kd_ref3, c.kd_ref3) = d.kd_ref3
                    AND COALESCE(a.kd_ref4, b.kd_ref4, c.kd_ref4) = d.kd_ref4
                    AND COALESCE(a.kd_ref5, b.kd_ref5, c.kd_ref5) = d.kd_ref5
                    AND COALESCE(a.kd_ref6, b.kd_ref6, c.kd_ref6) = d.kd_ref6
                WHERE
                    COALESCE(a.tahun, b.tahun, c.tahun) = :tahun
                    AND (NVL(a.jumlah_silpa, 0) + NVL(b.jum_sumber_dana, 0) - NVL(c.jum_belanja, 0)) > 0
                ORDER BY 
                    sisa DESC
            ";
    
            $data = DB::connection('oracle')->select($sql, ['tahun' => $tahun]);
    
            return response()->json([
                'success' => true,
                'tahun' => $tahun,
                'count' => count($data),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
}
