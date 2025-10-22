<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PaguBelanjaModel;
use Illuminate\Http\Request;
use App\Http\Resources\PaguBelanjaResource;
use Illuminate\Support\Facades\DB;

class PaguBelanjaController extends Controller
{
    /**
     * Tampilkan daftar Pagu Belanja (dengan pagination & search).
     */
    public function index(Request $request)
    {
        $query = PaguBelanjaModel::query();

        // Filter pencarian
        if ($search = $request->get('search')) {
            $searchColumns = [
                'kd_prog1',
                'kd_prog2',
                'kd_prog3',
                'kd_keg1',
                'kd_subkeg1',
                'kd_rekening1',
            ];
        
            $query->where(function ($query) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
        
        // Eager loading Urusan
        $data = PaguBelanjaModel::with('urusan')->orderBy('tahun_rek', 'desc')->paginate(10);

        // Lazy eager loading untuk semua relasi composite key
        $data->getCollection()->transform(function ($item) {
            $item->program      = $item->program;      // accessor Program
            $item->kegiatan     = $item->kegiatan;     // accessor Kegiatan
            $item->subkegiatan  = $item->subkegiatan;  // accessor SubKegiatan
            $item->rekening     = $item->rekening;     // accessor Rekening
            $item->bu           = $item->bu;           // accessor Bidang Urusan
            $item->skpd           = $item->skpd;           // accessor SKPD
            return $item;
        });
        
        // Mengembalikan collection
        return PaguBelanjaResource::collection($data);
        
    }

    /**
     * Simpan data Pagu Belanja baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_rek' => 'required|integer',
            'kd_urusan' => 'required|string|max:2',
            'kd_prog1' => 'required|string|max:2',
            'kd_prog2' => 'required|string|max:2',
            'kd_prog3' => 'required|string|max:2',
            'kd_keg1' => 'required|string|max:2',
            'kd_keg2' => 'required|string|max:2',
            'kd_keg3' => 'required|string|max:2',
            'kd_keg4' => 'required|string|max:2',
            'kd_keg5' => 'required|string|max:2',
            'kd_subkeg1' => 'required|string|max:2',
            'kd_subkeg2' => 'required|string|max:2',
            'kd_subkeg3' => 'required|string|max:2',
            'kd_subkeg4' => 'required|string|max:2',
            'kd_subkeg5' => 'required|string|max:2',
            'kd_subkeg6' => 'required|string|max:2',
            'kd_rekening1' => 'required|string|max:2',
            'kd_rekening2' => 'required|string|max:2',
            'kd_rekening3' => 'required|string|max:2',
            'kd_rekening4' => 'required|string|max:2',
            'kd_rekening5' => 'required|string|max:2',
            'kd_rekening6' => 'required|string|max:2',
            'jumlah_pagu' => 'required|numeric',
        ]);

        try {
            $pagu = PaguBelanjaModel::create($validated);

            return new PaguBelanjaResource($pagu);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Pagu Belanja sudah terdaftar.',
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
     * Tampilkan detail Pagu Belanja berdasarkan ID.
     */
    public function show($id)
    {
        try {
            $pagu = DB::connection('oracle')
                ->table(DB::raw('PAGU_BELANJA'))
                ->where('id_pb', $id)
                ->where('is_deleted', 0)
                ->first();

            if (!$pagu) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Pagu Belanja tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $pagu,
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
     * Update data Pagu Belanja.
     */
    public function update(Request $request, $id)
    {
        try {
            $pagu = PaguBelanjaModel::find($id);

            if (!$pagu) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Pagu Belanja tidak ditemukan',
                ], 404);
            }

            $pagu->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data Pagu Belanja berhasil diperbarui',
                'data' => new PaguBelanjaResource($pagu),
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
     * Soft delete data Pagu Belanja.
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('oracle')
                ->table('PAGU_BELANJA')
                ->where('id_pb', $id)
                ->update(['is_deleted' => 1]);

            if ($affected === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Pagu Belanja tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data Pagu Belanja berhasil dihapus (soft delete)',
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
