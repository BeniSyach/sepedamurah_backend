<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PaguBelanjaModel;
use Illuminate\Http\Request;
use App\Http\Resources\PaguBelanjaResource;
use App\Imports\PaguBelanjaImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class PaguBelanjaController extends Controller
{
    /**
     * Tampilkan daftar Pagu Belanja (dengan pagination & search).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
      
        // Mulai query pagubelanaja
        $query = PaguBelanjaModel::with('urusan');
        
        // Join tabel untuk pencarian
        $query->leftJoin('ref_urusan', 'pagu_belanja.kd_urusan', '=', 'ref_urusan.kd_urusan')
            ->leftJoin('ref_bidang_urusan', function($join) {
                $join->on('pagu_belanja.kd_bu1', '=', 'ref_bidang_urusan.kd_bu1')
                     ->on('pagu_belanja.kd_bu2', '=', 'ref_bidang_urusan.kd_bu2');
            })
            ->leftJoin('ref_program', function($join) {
                $join->on('pagu_belanja.kd_prog1', '=', 'ref_program.kd_prog1')
                     ->on('pagu_belanja.kd_prog2', '=', 'ref_program.kd_prog2')
                     ->on('pagu_belanja.kd_prog3', '=', 'ref_program.kd_prog3');
            })
            ->leftJoin('ref_kegiatan', function($join) {
                $join->on('pagu_belanja.kd_keg1', '=', 'ref_kegiatan.kd_keg1')
                     ->on('pagu_belanja.kd_keg2', '=', 'ref_kegiatan.kd_keg2')
                     ->on('pagu_belanja.kd_keg3', '=', 'ref_kegiatan.kd_keg3')
                     ->on('pagu_belanja.kd_keg4', '=', 'ref_kegiatan.kd_keg4')
                     ->on('pagu_belanja.kd_keg5', '=', 'ref_kegiatan.kd_keg5');
            })
            ->leftJoin('ref_subkegiatan', function($join) {
                $join->on('pagu_belanja.kd_subkeg1', '=', 'ref_subkegiatan.kd_subkeg1')
                     ->on('pagu_belanja.kd_subkeg2', '=', 'ref_subkegiatan.kd_subkeg2')
                     ->on('pagu_belanja.kd_subkeg3', '=', 'ref_subkegiatan.kd_subkeg3')
                     ->on('pagu_belanja.kd_subkeg4', '=', 'ref_subkegiatan.kd_subkeg4')
                     ->on('pagu_belanja.kd_subkeg5', '=', 'ref_subkegiatan.kd_subkeg5')
                     ->on('pagu_belanja.kd_subkeg6', '=', 'ref_subkegiatan.kd_subkeg6');
            })
            ->leftJoin('ref_rekening', function($join) {
                $join->on('pagu_belanja.kd_rekening1', '=', 'ref_rekening.kd_rekening1')
                     ->on('pagu_belanja.kd_rekening2', '=', 'ref_rekening.kd_rekening2')
                     ->on('pagu_belanja.kd_rekening3', '=', 'ref_rekening.kd_rekening3')
                     ->on('pagu_belanja.kd_rekening4', '=', 'ref_rekening.kd_rekening4')
                     ->on('pagu_belanja.kd_rekening5', '=', 'ref_rekening.kd_rekening5')
                     ->on('pagu_belanja.kd_rekening6', '=', 'ref_rekening.kd_rekening6');
            })
            ->leftJoin('ref_opd as skpd', function($join) {
                $join->on(DB::raw("TRIM(REPLACE(pagu_belanja.kd_opd1 || '.' || 
                                                     pagu_belanja.kd_opd2 || '.' || 
                                                     pagu_belanja.kd_opd3 || '.' || 
                                                     pagu_belanja.kd_opd4 || '.' || 
                                                     pagu_belanja.kd_opd5 || '.' || 
                                                     pagu_belanja.kd_opd6 || '.' || 
                                                     pagu_belanja.kd_opd7 || '.' || 
                                                     pagu_belanja.kd_opd8, ' ', ''))"), 
                            '=', DB::raw('skpd.kode_opd'));
            });
    
        // Filter search jika ada
        if ($search) {
            $searchLower = strtolower($search);
    
            $query->where(function($q) use ($searchLower) {
                $q->whereRaw('LOWER(ref_urusan.nm_urusan) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(ref_bidang_urusan.nm_bu) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(ref_program.nm_program) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(ref_kegiatan.nm_kegiatan) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(ref_subkegiatan.nm_subkegiatan) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(ref_rekening.nm_rekening) like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(skpd.nm_opd) like ?', ["%{$searchLower}%"]);
            });
        }
    
        // Pilih field dari PaguBelanja
        $data = $query->selectRaw('pagu_belanja.*')
                    ->where('is_deleted',0)
                    ->orderBy('pagu_belanja.tahun_rek', 'desc')
                    ->paginate(10);
    
        // Transform relasi Eloquent jika perlu
        $data->getCollection()->transform(function ($item) {
            $item->program      = $item->program;
            $item->kegiatan     = $item->kegiatan;
            $item->subkegiatan  = $item->subkegiatan;
            $item->rekening     = $item->rekening;
            $item->bu           = $item->bu;
            $item->skpd         = $item->skpd;
            return $item;
        });
    
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

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);
    
        // 1️⃣ Ambil versi terakhir
        $lastBerapax = PaguBelanjaModel::max('kd_berapax') ?? 0;
        $newBerapax  = $lastBerapax + 1;
    
        try {
            // 2️⃣ Import dulu (JANGAN sentuh versi lama)
            Excel::import(
                new PaguBelanjaImport($newBerapax),
                $request->file('file')
            );
    
            // 3️⃣ Jika sukses → nonaktifkan versi lama
            if ($lastBerapax > 0) {
                PaguBelanjaModel::where('kd_berapax', $lastBerapax)
                    ->update(['is_deleted' => 1]);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Import Excel berhasil'
            ]);
    
        } catch (\Throwable $e) {
    
            // 4️⃣ Jika gagal → HAPUS PERMANENT versi baru
            PaguBelanjaModel::where('kd_berapax', $newBerapax)->delete();
    
            return response()->json([
                'status' => false,
                'message' => 'Import gagal, data dikembalikan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function restoreLastVersion()
    {
        // Versi terbaru (yang sekarang aktif)
        $current = PaguBelanjaModel::where('is_deleted', 0)
            ->max('kd_berapax');

        if (!$current) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada versi aktif'
            ], 404);
        }

        // Versi sebelumnya
        $previous = PaguBelanjaModel::where('kd_berapax', '<', $current)
            ->max('kd_berapax');

        if (!$previous) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada versi sebelumnya'
            ], 404);
        }

        DB::connection('oracle')->beginTransaction();

        try {
            // Nonaktifkan versi salah
            PaguBelanjaModel::where('kd_berapax', $current)
                ->update(['is_deleted' => 1]);
            // 🔥 HAPUS PERMANENT versi salah
            PaguBelanjaModel::where('kd_berapax', $current)->delete();

            // Aktifkan versi lama
            PaguBelanjaModel::where('kd_berapax', $previous)
                ->update(['is_deleted' => 0]);

            DB::connection('oracle')->commit();

            return response()->json([
                'status' => true,
                'message' => "Berhasil restore ke versi {$previous}"
            ]);

        } catch (\Throwable $e) {
            DB::connection('oracle')->rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Restore gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
