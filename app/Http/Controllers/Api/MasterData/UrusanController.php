<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\UrusanModel;
use Illuminate\Http\Request;
use App\Http\Resources\UrusanResource;
use Illuminate\Support\Facades\DB;

class UrusanController extends Controller
{
    /**
     * Tampilkan daftar urusan (dengan pagination).
     */
    public function index(Request $request)
    {
        $query = UrusanModel::query();

        if ($search = $request->get('search')) {
            $query->where('nm_urusan', 'like', "%{$search}%")
                  ->orWhere('kd_urusan', 'like', "%{$search}%");
        }

        $data = $query->orderBy('kd_urusan', 'asc')->paginate(10);

        return UrusanResource::collection($data);
    }

    /**
     * Simpan urusan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_urusan' => 'required|string|max:10|unique:ref_urusan,kd_urusan',
            'nm_urusan' => 'required|string|max:255',
        ]);
    
        try {
            $urusan = UrusanModel::create($validated);
            return new UrusanResource($urusan);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kode urusan sudah terdaftar.',
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
     * Tampilkan detail urusan.
     */
 

    public function show($id)
    {
        try {
            // Query langsung ke Oracle
            $urusan = DB::connection('oracle')
            ->table(DB::raw('REF_URUSAN'))
            ->whereRaw('TRIM(KD_URUSAN) = ?', [$id])
            ->whereRaw('DELETED_AT IS NULL')
            ->first();
        
    
            if (!$urusan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data urusan tidak ditemukan',
                ], 404);
            }
    
            return response()->json([
                'status' => true,
                'data' => $urusan,
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
     * Update data urusan.
     */
    public function update(Request $request, $id)
    {
        try {
            // cari data berdasarkan kd_urusan yang di-trim
            $urusan = UrusanModel::whereRaw('TRIM(KD_URUSAN) = ?', [trim($id)])->first();
    
            if (!$urusan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data urusan tidak ditemukan',
                ], 404);
            }
    
            // validasi input
            $validated = $request->validate([
                'nm_urusan' => 'required|string|max:255',
            ]);
    
            // update langsung via query builder
            $updated = DB::table('REF_URUSAN')
                ->whereRaw('TRIM(KD_URUSAN) = ?', [trim($id)])
                ->update([
                    'NM_URUSAN' => $validated['nm_urusan'],
                    'UPDATED_AT' => now(), // kalau kamu pakai timestamp
                ]);
    
            if ($updated === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada perubahan data (data mungkin sudah sama)',
                ]);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Data urusan berhasil diperbarui',
                'data' => new UrusanResource($urusan),
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
     * Soft delete data urusan.
     */

     public function destroy($id)
     {
         try {
             $affected = DB::connection('oracle')
                 ->table('REF_URUSAN')
                 ->whereRaw('TRIM(KD_URUSAN) = ?', [trim($id)])
                 ->update([
                     'DELETED_AT' => now(),
                 ]);
     
             if ($affected === 0) {
                 return response()->json([
                     'status' => false,
                     'message' => 'Data urusan tidak ditemukan',
                 ], 404);
             }
     
             return response()->json([
                 'status' => true,
                 'message' => 'Data urusan berhasil dihapus (soft delete)',
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
