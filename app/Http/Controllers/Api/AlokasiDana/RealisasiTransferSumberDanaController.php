<?php

namespace App\Http\Controllers\Api\AlokasiDana;

use App\Http\Controllers\Controller;
use App\Models\RealisasiSumberDanaModel;
use Illuminate\Http\Request;
use App\Http\Resources\RealisasiSumberDanaResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RealisasiTransferSumberDanaController extends Controller
{

    // public function index(Request $request)
    // {
    //     // === FILTER TANGGAL ===
    //     $tglAwal  = $request->get('tgl_awal', date('Y-m-01'));
    //     $tglAkhir = $request->get('tgl_akhir', date('Y-m-t'));
    //     $tahun = $request->get('tahun');
    
    //     $query = RealisasiSumberDanaModel::select([
    //         'kd_ref1', 'kd_ref2', 'kd_ref3',
    //         'kd_ref4', 'kd_ref5', 'kd_ref6',
    
    //         DB::raw('MAX(nm_sumber) AS nm_sumber'),
    
    //         // ================= BULAN JANUARI =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 1
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_jan
    //         "),
    
    //         // ================= FEBRUARI =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 2
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_feb
    //         "),
    
    //         // ================= MARET =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 3
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_mar
    //         "),
    
    //         // ================= APRIL =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 4
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_apr
    //         "),
    
    //         // ================= MEI =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 5
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_may
    //         "),
    
    //         // ================= JUNI =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 6
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_jun
    //         "),
    
    //         // ================= JULI =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 7
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_jul
    //         "),
    
    //         // ================= AGUSTUS =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 8
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_aug
    //         "),
    
    //         // ================= SEPTEMBER =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 9
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_sep
    //         "),
    
    //         // ================= OKTOBER =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 10
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_oct
    //         "),
    
    //         // ================= NOVEMBER =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 11
    //                      AND tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_nov
    //         "),
    
    //         // ================= DESEMBER (PAKAI RENTANG) =================
    //         DB::raw("
    //             SUM(
    //                 CASE
    //                     WHEN EXTRACT(MONTH FROM tgl_diterima) = 12
    //                      AND tgl_diterima BETWEEN
    //                          TO_DATE('$tglAwal','YYYY-MM-DD')
    //                          AND TO_DATE('$tglAkhir','YYYY-MM-DD')
    //                     THEN jumlah_sumber ELSE 0
    //                 END
    //             ) AS total_dec
    //         "),
    //     ])
    
    //     // 🔥 BATAS DATA GLOBAL
    //     ->whereNull('deleted_at')
    //     ->whereRaw("tgl_diterima <= TO_DATE('$tglAkhir','YYYY-MM-DD')")
    //     ->where('tahun', $tahun)
    
    //     // GROUPING
    //     ->groupBy(
    //         'kd_ref1', 'kd_ref2', 'kd_ref3',
    //         'kd_ref4', 'kd_ref5', 'kd_ref6'
    //     )
    
    //     // ORDER
    //     ->orderBy('kd_ref1')
    //     ->orderBy('kd_ref2')
    //     ->orderBy('kd_ref3')
    //     ->orderBy('kd_ref4')
    //     ->orderBy('kd_ref5')
    //     ->orderBy('kd_ref6');
    
    //     // ================= SEARCH =================
    //     if ($search = $request->get('search')) {
    //         $query->havingRaw(
    //             "LOWER(MAX(nm_sumber)) LIKE ?",
    //             ['%' . strtolower($search) . '%']
    //         );
    //     }
    
    //     $data = $query->get();
    
    //     return response()->json([
    //         'total'     => $data->count(),
    //         'tgl_awal'  => $tglAwal,
    //         'tgl_akhir' => $tglAkhir,
    //         'data'      => $data,
    //     ]);
    // }

    public function index(Request $request)
    {
        $tglAwal  = $request->get('tgl_awal', date('Y-m-01'));
        $tglAkhir = $request->get('tgl_akhir', date('Y-m-t'));
        $tahun    = $request->get('tahun');

        $bulanAwal  = date('n', strtotime($tglAwal));
        $bulanAkhir = date('n', strtotime($tglAkhir));

        $query = RealisasiSumberDanaModel::select([
            'kd_ref1','kd_ref2','kd_ref3',
            'kd_ref4','kd_ref5','kd_ref6',
            DB::raw('MAX(nm_sumber) AS nm_sumber'),
        ]);

        // helper buat generate SUM CASE
        function generateCase($bulan, $alias, $tahun, $tglAwal, $tglAkhir, $bulanAwal, $bulanAkhir)
        {
            $startOfMonth = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
            $endOfMonth   = date("Y-m-t", strtotime($startOfMonth));

            if ($bulan < $bulanAwal) {
                $condition = "
                    tgl_diterima BETWEEN
                    TO_DATE('$startOfMonth','YYYY-MM-DD')
                    AND TO_DATE('$endOfMonth','YYYY-MM-DD')
                ";
            } elseif ($bulan == $bulanAwal && $bulanAwal == $bulanAkhir) {
                $condition = "
                    tgl_diterima BETWEEN
                    TO_DATE('$tglAwal','YYYY-MM-DD')
                    AND TO_DATE('$tglAkhir','YYYY-MM-DD')
                ";
            } elseif ($bulan == $bulanAwal) {
                $condition = "
                    tgl_diterima BETWEEN
                    TO_DATE('$tglAwal','YYYY-MM-DD')
                    AND TO_DATE('$endOfMonth','YYYY-MM-DD')
                ";
            } elseif ($bulan > $bulanAwal && $bulan < $bulanAkhir) {
                $condition = "
                    tgl_diterima BETWEEN
                    TO_DATE('$startOfMonth','YYYY-MM-DD')
                    AND TO_DATE('$endOfMonth','YYYY-MM-DD')
                ";
            } elseif ($bulan == $bulanAkhir) {
                $condition = "
                    tgl_diterima BETWEEN
                    TO_DATE('$startOfMonth','YYYY-MM-DD')
                    AND TO_DATE('$tglAkhir','YYYY-MM-DD')
                ";
            } else {
                $condition = "1=0";
            }

            return DB::raw("
                SUM(
                    CASE
                        WHEN $condition
                        THEN jumlah_sumber
                        ELSE 0
                    END
                ) AS total_$alias
            ");
        }

        $query->addSelect(generateCase(1,'jan',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(2,'feb',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(3,'mar',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(4,'apr',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(5,'may',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(6,'jun',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(7,'jul',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(8,'aug',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(9,'sep',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(10,'oct',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(11,'nov',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));
        $query->addSelect(generateCase(12,'dec',$tahun,$tglAwal,$tglAkhir,$bulanAwal,$bulanAkhir));

        $query->whereNull('deleted_at')
            ->where('tahun', $tahun)
            ->groupBy(
                'kd_ref1','kd_ref2','kd_ref3',
                'kd_ref4','kd_ref5','kd_ref6'
            )
            ->orderBy('kd_ref1')
            ->orderBy('kd_ref2')
            ->orderBy('kd_ref3')
            ->orderBy('kd_ref4')
            ->orderBy('kd_ref5')
            ->orderBy('kd_ref6');

        if ($search = $request->get('search')) {
            $query->havingRaw(
                "LOWER(MAX(nm_sumber)) LIKE ?",
                ['%' . strtolower($search) . '%']
            );
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function detailTFSD(Request $request)
    {
        // Ambil parameter dari frontend
        $tahun   = $request->input('tahun');
        $search  = $request->input('search', '');
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
    
        $kd_ref1 = $request->input('kd_ref1');
        $kd_ref2 = $request->input('kd_ref2');
        $kd_ref3 = $request->input('kd_ref3');
        $kd_ref4 = $request->input('kd_ref4');
        $kd_ref5 = $request->input('kd_ref5');
        $kd_ref6 = $request->input('kd_ref6');
    
        // Query dasar
        $query = RealisasiSumberDanaModel::query()
            ->where('tahun', $tahun);
    
        // Filter 6 KD_REF (untuk detail)
        if ($kd_ref1) $query->where('kd_ref1', $kd_ref1);
        if ($kd_ref2) $query->where('kd_ref2', $kd_ref2);
        if ($kd_ref3) $query->where('kd_ref3', $kd_ref3);
        if ($kd_ref4) $query->where('kd_ref4', $kd_ref4);
        if ($kd_ref5) $query->where('kd_ref5', $kd_ref5);
        if ($kd_ref6) $query->where('kd_ref6', $kd_ref6);
    
        // Search by nm_sumber
        if (!empty($search)) {
            $query->where('nm_sumber', 'LIKE', "%$search%");
        }
        $query->orderBy('tgl_diterima', 'desc');
    
        // Ambil data paginated
        $result = $query->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ]
        ]);
    }    

    /**
     * Simpan sumber dana baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_ref1' => 'required|string|max:1',
            'kd_ref2' => 'required|string|max:1',
            'kd_ref3' => 'nullable|string|max:2',
            'kd_ref4' => 'nullable|string|max:2',
            'kd_ref5' => 'nullable|string|max:2',
            'kd_ref6' => 'nullable|string|max:4',
            'nm_sumber' => 'required|string|max:500',
            'tgl_diterima' => 'required|date',
            // 'tahun' => 'required|string|max:4',
            'jumlah_sumber' => 'nullable|numeric',
            'keterangan_2' => 'nullable|string|max:255',
        ]);

        try {
            $validated['tahun'] = Carbon::parse($validated['tgl_diterima'])->format('Y');
          // Hitung data sama berdasarkan kode ref + tahun
        $count_same = RealisasiSumberDanaModel::where([
            'kd_ref1' => $validated['kd_ref1'],
            'kd_ref2' => $validated['kd_ref2'],
            'kd_ref3' => $validated['kd_ref3'],
            'kd_ref4' => $validated['kd_ref4'],
            'kd_ref5' => $validated['kd_ref5'],
            'kd_ref6' => $validated['kd_ref6'],
            'tahun'   => $validated['tahun'],
        ])->count();

       
        // Set nilai keterangan = count_same + 1
        $validated['keterangan'] = $count_same + 1;

        // Simpan data
        $sumber = RealisasiSumberDanaModel::create($validated);

        return new RealisasiSumberDanaResource($sumber);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data dengan kombinasi key sudah terdaftar.',
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
     * Tampilkan detail sumber dana.
     */
    public function show($id)
    {
        try {
            $sumber = DB::connection('oracle')
                ->table(DB::raw('SUMBER_DANA'))
                ->whereRaw('ID = ?', [$id])
                ->first();

            if (!$sumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $sumber,
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
     * Update sumber dana.
     */
    public function update(Request $request, $id)
    {
        try {
            $sumber = RealisasiSumberDanaModel::find($id);

            if (!$sumber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'kd_ref1' => 'required|string|max:1',
                'kd_ref2' => 'required|string|max:1',
                'kd_ref3' => 'nullable|string|max:2',
                'kd_ref4' => 'nullable|string|max:2',
                'kd_ref5' => 'nullable|string|max:2',
                'kd_ref6' => 'nullable|string|max:4',
                'nm_sumber' => 'required|string|max:300',
                'tgl_diterima' => 'required|date',
                'tahun' => 'required|string|max:4',
                'jumlah_sumber' => 'nullable|numeric',
                'keterangan' => 'required|integer',
                'keterangan_2' => 'nullable|string|max:255',
            ]);

            $sumber->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new RealisasiSumberDanaResource($sumber),
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
     * Soft delete sumber dana.
     */
    public function destroy($id)
    {
        try {
            // Cari data, termasuk yang sudah soft delete
            $record = RealisasiSumberDanaModel::withTrashed()->find($id);
    
            if (!$record) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }
    
            // Hapus permanen
            $record->forceDelete();
    
            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil dihapus permanen (force delete)',
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    

    public function sumberDanaPajak()
    {
        // Path file log
        $logFile = storage_path('logs/sumber_dana_pajak.log');
        $tahun = date('Y');
        try {
            // Log: Memulai proses
            file_put_contents($logFile, "[" . now() . "] Memulai proses sumber_dana_pajak...\n", FILE_APPEND);

            // Parameter referensi
            $KD_REF1 = '4';
            $KD_REF2 = '1';
            $KD_REF3 = '01';
            $KD_REF4 = '00';
            $KD_REF5 = '00';
            $KD_REF6 = '0000';

            // Hapus data lama sebelum insert ulang
            RealisasiSumberDanaModel::where([
                'kd_ref1' => $KD_REF1,
                'kd_ref2' => $KD_REF2,
                'kd_ref3' => $KD_REF3,
                'kd_ref4' => $KD_REF4,
                'kd_ref5' => $KD_REF5,
                'kd_ref6' => $KD_REF6,
                'tahun'   => $tahun,
            ])->forceDelete();

            file_put_contents($logFile, "[" . now() . "] Data lama berhasil dihapus.\n", FILE_APPEND);

         

            $data = DB::connection('oracle')->select("
                SELECT 
                    TO_CHAR(TRUNC(tgl_bayar), 'YYYY-MM-DD') AS tanggal_bayar, 
                    SUM(jum_bayar) AS total_penerimaan
                FROM v_pembayaran_gab@pajak_daerah
                WHERE EXTRACT(YEAR FROM tgl_bayar) = :tahun
                GROUP BY TRUNC(tgl_bayar)
                ORDER BY TRUNC(tgl_bayar) ASC
            ", [
                'tahun' => $tahun
            ]);
            
        

            if (count($data) === 0) {
                throw new \Exception("Tidak ada data yang ditemukan.");
            }

            file_put_contents($logFile, "[" . now() . "] Ditemukan " . count($data) . " baris data.\n", FILE_APPEND);

            foreach ($data as $row) {
                $tglDiterima = $row->tanggal_bayar;
                $totalPenerimaan = $row->total_penerimaan;

                // Hitung count_same
                $countSame = RealisasiSumberDanaModel::where([
                    'kd_ref1' => $KD_REF1,
                    'kd_ref2' => $KD_REF2,
                    'kd_ref3' => $KD_REF3,
                    'kd_ref4' => $KD_REF4,
                    'kd_ref5' => $KD_REF5,
                    'kd_ref6' => $KD_REF6,
                    'tahun'   => date('Y', strtotime($tglDiterima))
                ])->count();

                // Insert data baru
                $record = new RealisasiSumberDanaModel();
                $record->kd_ref1       = $KD_REF1;
                $record->kd_ref2       = $KD_REF2;
                $record->kd_ref3       = $KD_REF3;
                $record->kd_ref4       = $KD_REF4;
                $record->kd_ref5       = $KD_REF5;
                $record->kd_ref6       = $KD_REF6;
                $record->nm_sumber     = 'Pajak Daerah';
                $record->tahun         = date('Y', strtotime($tglDiterima));
                $record->jumlah_sumber = $totalPenerimaan;
                $record->keterangan    = $countSame + 1;
                $record->tgl_diterima  = $tglDiterima;
                $record->save();

                file_put_contents($logFile, "[" . now() . "] Data berhasil diinsert untuk tanggal $tglDiterima dengan jumlah $totalPenerimaan.\n", FILE_APPEND);
            }

            file_put_contents($logFile, "[" . now() . "] Proses selesai!\n", FILE_APPEND);

            return response()->json([
                'status' => 'success',
                'message' => 'Proses selesai!'
            ]);
        } catch (\Exception $e) {
            file_put_contents($logFile, "[" . now() . "] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
