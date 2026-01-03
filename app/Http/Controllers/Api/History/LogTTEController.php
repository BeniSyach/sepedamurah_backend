<?php

namespace App\Http\Controllers\Api\History;

use App\Http\Controllers\Controller;
use App\Models\LogTTEModel;
use Illuminate\Http\Request;
use App\Http\Resources\LogTTEResource;
use Illuminate\Support\Facades\DB;

class LogTTEController extends Controller
{
    /**
     * List Log TTE (pagination + search)
     */
    public function index(Request $request)
    {
        $query = DB::connection('oracle')
            ->table('tte_history')
            ->whereNull('deleted_at');
    
        // 🔎 SEARCH
        if ($search = $request->get('search')) {
            $searchLower = strtolower(trim($search));
    
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(kategori) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(tte) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(nama_penandatangan) LIKE ?', ["%{$searchLower}%"]);
            });
        }
    
        // ✅ FILTER TAHUN JIKA ADA
        if ($request->filled('tahun')) {
            $query->whereRaw(
                'EXTRACT(YEAR FROM tgl_tte) = ?',
                [$request->tahun]
            );
        }
    
        $data = $query
            ->orderBy('tgl_tte', 'desc')
            ->paginate($request->get('per_page', 10));
    
        return LogTTEResource::collection($data);
    }

    /**
     * Simpan log baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_berkas' => 'required|integer',
            'kategori' => 'required|string|max:50',
            'tte' => 'required|string|max:255',
            'status' => 'required|integer',
            'tgl_tte' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
        ]);

        try {
            // Ambil ID dari sequence trigger Oracle
            $id = DB::connection('oracle')->selectOne('SELECT NO_TTE_HISTORY.NEXTVAL AS id FROM dual')->id;

            DB::connection('oracle')->table('TTE_HISTORY')->insert(array_merge($validated, [
                'id' => $id,
                'date_created' => now(),
            ]));

            $log = DB::connection('oracle')->table('TTE_HISTORY')->where('id', $id)->first();

            return new LogTTEResource($log);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail log TTE
     */
    public function show($id)
    {
        $log = DB::connection('oracle')->table('TTE_HISTORY')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $log,
        ]);
    }

    /**
     * Update log
     */
    public function update(Request $request, $id)
    {
        $log = DB::connection('oracle')->table('TTE_HISTORY')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->first();

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'id_berkas' => 'required|integer',
            'kategori' => 'required|string|max:50',
            'tte' => 'required|string|max:255',
            'status' => 'required|integer',
            'tgl_tte' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
            'id_penandatangan' => 'nullable|integer',
            'nama_penandatangan' => 'nullable|string|max:255',
        ]);

        try {
            DB::connection('oracle')->table('TTE_HISTORY')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now(),
                ]));

            $updatedLog = DB::connection('oracle')->table('TTE_HISTORY')->where('id', $id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $updatedLog,
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
     * Soft delete log
     */
    public function destroy($id)
    {
        $affected = DB::connection('oracle')->table('TTE_HISTORY')
                        ->where('id', $id)
                        ->whereNull('deleted_at')
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
    }
}
