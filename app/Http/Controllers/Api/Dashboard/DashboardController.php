<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AksesDPAModel;
use App\Models\DPAModel;
use App\Models\LaporanDPAModel;
use App\Models\LaporanFungsionalModel;
use App\Models\PermohonanSPDModel;
use App\Models\SKPDModel;
use App\Models\SP2DKirimModel;
use App\Models\SP2DModel;
use App\Models\SPDTerkirimModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from  = $request->from;
        $to    = $request->to;
        $tahun = $request->tahun;
    
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        /**
         * ==========================================================================================
         * Helper filter tanggal / tahun
         * ==========================================================================================
         */
        $applyDateFilter = function ($query, $column) use ($from, $to, $tahun) {
    
            if ($from && $to) {
    
                $query->whereBetween($column, [$from, $to]);
    
            } elseif ($tahun) {
    
                // Oracle Friendly
                $query->whereRaw(
                    "EXTRACT(YEAR FROM {$column}) = ?",
                    [$tahun]
                );
            }
        };
    
        /**
         * ==========================================================================================
         * Helper filter OPD
         * ==========================================================================================
         */
        $applyOPDFilter = function ($query) use (
            $kd_opd1,
            $kd_opd2,
            $kd_opd3,
            $kd_opd4,
            $kd_opd5
        ) {
    
            if ($kd_opd1) {
                $query->where('kd_opd1', $kd_opd1);
            }
    
            if ($kd_opd2) {
                $query->where('kd_opd2', $kd_opd2);
            }
    
            if ($kd_opd3) {
                $query->where('kd_opd3', $kd_opd3);
            }
    
            if ($kd_opd4) {
                $query->where('kd_opd4', $kd_opd4);
            }
    
            if ($kd_opd5) {
                $query->where('kd_opd5', $kd_opd5);
            }
        };
    
        // ==========================================================================================
        // TOTAL GABUNGAN SPD
        // ==========================================================================================
    
        $permohonanSPD = DB::table('permohonan_spd')
            ->whereNull('deleted_at');
    
        $spdTerkirim = DB::table('spd_terkirim')
            ->whereNull('deleted_at');
    
        $applyDateFilter($permohonanSPD, 'tanggal_upload');
        $applyDateFilter($spdTerkirim, 'tanggal_upload');
    
        $applyOPDFilter($permohonanSPD);
        $applyOPDFilter($spdTerkirim);
    
        $totalSPDGabungan = $permohonanSPD
            ->select('id')
            ->union(
                $spdTerkirim->select('id_berkas as id')
            )
            ->distinct()
            ->count();
    
        // ==========================================================================================
        // SPD
        // ==========================================================================================
    
        // TOTAL SPD TERVERIFIKASI
        $spdTerverifikasi = PermohonanSPDModel::whereNotNull('diterima');
    
        $applyDateFilter($spdTerverifikasi, 'diterima');
        $applyOPDFilter($spdTerverifikasi);
    
        $totalSPDTerverifikasi = $spdTerverifikasi->count();
    
        // TOTAL SPD DITOLAK
        $spdDitolak = PermohonanSPDModel::whereNotNull('ditolak');
    
        $applyDateFilter($spdDitolak, 'ditolak');
        $applyOPDFilter($spdDitolak);
    
        $totalSPDDitolak = $spdDitolak->count();
    
        // TOTAL SPD TTE
        $spdTTE = SPDTerkirimModel::whereNotNull('tgl_tte');
    
        $applyDateFilter($spdTTE, 'tgl_tte');
        $applyOPDFilter($spdTTE);
    
        $totalSPDTTE = $spdTTE->count();
    
        // ==========================================================================================
        // SP2D
        // ==========================================================================================
    
        // TOTAL PERMOHONAN SP2D
        $sp2dPermohonan = SP2DModel::query();
    
        $applyDateFilter($sp2dPermohonan, 'tanggal_upload');
        $applyOPDFilter($sp2dPermohonan);
    
        $totalSP2DPermohonan = $sp2dPermohonan->count();
    
        // TOTAL SP2D TERVERIFIKASI
        $sp2dTerverifikasi = SP2DModel::whereNotNull('diterima');
    
        $applyDateFilter($sp2dTerverifikasi, 'diterima');
        $applyOPDFilter($sp2dTerverifikasi);
    
        $totalSP2DTerverifikasi = $sp2dTerverifikasi->count();
    
        // TOTAL SP2D DITOLAK
        $sp2dDitolak = SP2DModel::whereNotNull('ditolak');
    
        $applyDateFilter($sp2dDitolak, 'ditolak');
        $applyOPDFilter($sp2dDitolak);
    
        $totalSP2DDitolak = $sp2dDitolak->count();
    
        // TOTAL SP2D TTE
        $sp2dTTE = SP2DKirimModel::whereNotNull('tgl_tte');
    
        $applyDateFilter($sp2dTTE, 'tgl_tte');
        $applyOPDFilter($sp2dTTE);
    
        $totalSP2DTTE = $sp2dTTE->count();
    
        // ==========================================================================================
        // RESPONSE
        // ==========================================================================================
    
        return response()->json([
            'status' => true,
            'data' => [
    
                // SPD
                'total_permohonan_spd'      => $totalSPDGabungan,
                'total_spd_terverifikasi'   => $totalSPDTerverifikasi,
                'total_spd_ditolak'         => $totalSPDDitolak,
                'total_spd_tte'             => $totalSPDTTE,
    
                // SP2D
                'total_permohonan_sp2d'     => $totalSP2DPermohonan,
                'total_sp2d_terverifikasi'  => $totalSP2DTerverifikasi,
                'total_sp2d_ditolak'        => $totalSP2DDitolak,
                'total_sp2d_tte'            => $totalSP2DTTE,
            ]
        ]);
    }
    
    public function berkas_masuk_sp2d(Request $request)
    {
        $perPage     = $request->get('per_page', 5);
        $orderColumn = 'sp2d.tanggal_upload';
        $orderDir    = 'desc';
    
        // ==========================
        // QUERY DASAR
        // ==========================
        $query = SP2DModel::query()
            ->with([
                'rekening',
                'sumberDana',
                'sp2dkirim'
            ])
            ->whereNull('sp2d.deleted_at')
    
            ->join('ref_opd', function ($join) {
                $join->on('sp2d.kd_opd1', '=', 'ref_opd.kd_opd1')
                    ->on('sp2d.kd_opd2', '=', 'ref_opd.kd_opd2')
                    ->on('sp2d.kd_opd3', '=', 'ref_opd.kd_opd3')
                    ->on('sp2d.kd_opd4', '=', 'ref_opd.kd_opd4')
                    ->on('sp2d.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
    
            ->select(
                'sp2d.*',
                'ref_opd.nm_opd'
            );
    
        // ==========================
        // FILTER OPD
        // ==========================
        if ($request->filled('kd_opd1')) {
            $query->where('sp2d.kd_opd1', $request->kd_opd1);
        }
    
        if ($request->filled('kd_opd2')) {
            $query->where('sp2d.kd_opd2', $request->kd_opd2);
        }
    
        if ($request->filled('kd_opd3')) {
            $query->where('sp2d.kd_opd3', $request->kd_opd3);
        }
    
        if ($request->filled('kd_opd4')) {
            $query->where('sp2d.kd_opd4', $request->kd_opd4);
        }
    
        if ($request->filled('kd_opd5')) {
            $query->where('sp2d.kd_opd5', $request->kd_opd5);
        }
    
        // ==========================
        // FILTER BERKAS MASUK
        // ==========================
        $query->whereNull('sp2d.proses')
            ->whereNull('sp2d.diterima')
            ->whereNull('sp2d.ditolak');
    
        // ==========================
        // ORDER & PAGINATION
        // ==========================
        $data = $query
            ->orderBy($orderColumn, $orderDir)
            ->paginate($perPage);
    
        // ==========================
        // TRANSFORM DATA
        // ==========================
        $data->getCollection()->transform(function ($item) {
    
            $item->program     = $item->program;
            $item->kegiatan    = $item->kegiatan;
            $item->subkegiatan = $item->subkegiatan;
            $item->rekening    = $item->rekening;
            $item->bu          = $item->bu;
            $item->skpd        = $item->skpd;
    
            if ($item->relationLoaded('rekening')) {
                $item->rekening->transform(function ($rek) {
                    $rek->program     = $rek->program;
                    $rek->kegiatan    = $rek->kegiatan;
                    $rek->subkegiatan = $rek->subkegiatan;
                    $rek->rekening    = $rek->rekening;
                    $rek->bu          = $rek->bu;
                    $rek->urusan      = $rek->urusan;
    
                    return $rek;
                });
            }
    
            if ($item->relationLoaded('sumberDana')) {
                $item->sumberDana->transform(function ($sd) {
                    $sd->referensi = $sd->sumberDana;
    
                    return $sd;
                });
            }
    
            return $item;
        });
    
        // ==========================
        // RESPONSE
        // ==========================
        return response()->json([
            'success' => true,
            'message' => 'Daftar SP2D berhasil diambil',
            'data'    => $data->items(),
    
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
    
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ]);
    }

    public function chartSp2dPerBulan(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
    
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        // ======================================================
        // QUERY
        // ======================================================
        $query = SP2DModel::query()
            ->select(
                DB::raw("TO_CHAR(tanggal_upload, 'MM') AS bulan"),
                DB::raw("COUNT(*) AS total")
            )
            ->whereNull('deleted_at')
            ->whereRaw("EXTRACT(YEAR FROM tanggal_upload) = ?", [$tahun]);
    
        // ======================================================
        // FILTER OPD
        // ======================================================
        if ($kd_opd1) {
            $query->where('kd_opd1', $kd_opd1);
        }
    
        if ($kd_opd2) {
            $query->where('kd_opd2', $kd_opd2);
        }
    
        if ($kd_opd3) {
            $query->where('kd_opd3', $kd_opd3);
        }
    
        if ($kd_opd4) {
            $query->where('kd_opd4', $kd_opd4);
        }
    
        if ($kd_opd5) {
            $query->where('kd_opd5', $kd_opd5);
        }
    
        // ======================================================
        // GROUPING
        // ======================================================
        $data = $query
            ->groupBy(DB::raw("TO_CHAR(tanggal_upload, 'MM')"))
            ->orderBy(DB::raw("TO_CHAR(tanggal_upload, 'MM')"))
            ->get();
    
        // ======================================================
        // FORMAT BULAN
        // ======================================================
        $namaBulan = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'Mei',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Agu',
            '09' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Des',
        ];
    
        // ======================================================
        // DEFAULT 12 BULAN
        // ======================================================
        $result = [
            'labels' => array_values($namaBulan),
            'values' => array_fill(0, 12, 0),
        ];
    
        // ======================================================
        // MAP DATA KE BULAN
        // ======================================================
        foreach ($data as $row) {
    
            $bulanIndex = (int)$row->bulan - 1;
    
            $result['values'][$bulanIndex] = (int)$row->total;
        }
    
        // ======================================================
        // RESPONSE
        // ======================================================
        return response()->json([
            'status' => true,
            'tahun' => (int)$tahun,
            'chart' => $result,
            'raw' => $data,
        ]);
    }

    public function tableCheckFungsional(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        
        // Get tahun list (3 tahun kebelakang dan 3 tahun kedepan)
        $currentYear = date('Y');
        $tahunList = [];
        for ($i = $currentYear - 3; $i <= $currentYear + 3; $i++) {
            $tahunList[] = (string)$i;
        }

        // Filter berdasarkan kd_opd jika ada request
        $kdOpd1 = $request->input('kd_opd1');
        $kdOpd2 = $request->input('kd_opd2');
        $kdOpd3 = $request->input('kd_opd3');
        $kdOpd4 = $request->input('kd_opd4');
        $kdOpd5 = $request->input('kd_opd5');

        // Get data penerimaan (jenis_berkas = 'Penerimaan')
        $dataMasuk = $this->getDataByJenisBerkas('Penerimaan', $tahun, $kdOpd1, $kdOpd2, $kdOpd3, $kdOpd4, $kdOpd5);

        // Get data pengeluaran (jenis_berkas = 'Pengeluaran')
        $dataKeluar = $this->getDataByJenisBerkas('Pengeluaran', $tahun, $kdOpd1, $kdOpd2, $kdOpd3, $kdOpd4, $kdOpd5);

        return response()->json([
            'success' => true,
            'data' => [
                'tahun_list' => $tahunList,
                'tahun_selected' => $tahun,
                'penerimaan' => $dataMasuk,
                'pengeluaran' => $dataKeluar,
            ]
        ]);
    }

    private function getDataByJenisBerkas($jenisBerkas, $tahun, $kdOpd1 = null, $kdOpd2 = null, $kdOpd3 = null, $kdOpd4 = null, $kdOpd5 = null)
    {
        // Query untuk mendapatkan list SKPD
        $query = LaporanFungsionalModel::select(
            'kd_opd1',
            'kd_opd2',
            'kd_opd3',
            'kd_opd4',
            'kd_opd5'
        )
            ->where('jenis_berkas', $jenisBerkas)
            ->where('tahun', $tahun)
            ->whereNotNull('diterima'); // hanya yang sudah diverifikasi

        // Filter berdasarkan kd_opd jika ada
        if ($kdOpd1 !== null) {
            $query->where('kd_opd1', $kdOpd1);
        }
        if ($kdOpd2 !== null) {
            $query->where('kd_opd2', $kdOpd2);
        }
        if ($kdOpd3 !== null) {
            $query->where('kd_opd3', $kdOpd3);
        }
        if ($kdOpd4 !== null) {
            $query->where('kd_opd4', $kdOpd4);
        }
        if ($kdOpd5 !== null) {
            $query->where('kd_opd5', $kdOpd5);
        }

        $skpdList = $query->distinct()->get();

        $result = [];

        foreach ($skpdList as $skpd) {
            $namaSkpd = $this->getNamaSkpd(
                $skpd->kd_opd1,
                $skpd->kd_opd2,
                $skpd->kd_opd3,
                $skpd->kd_opd4,
                $skpd->kd_opd5
            );

            // Initialize bulan array (1-12) with false
            $bulanData = array_fill(1, 12, false);

            // Get data per bulan untuk SKPD ini
            $laporanPerBulan = LaporanFungsionalModel::selectRaw("EXTRACT(MONTH FROM tanggal_upload) as bulan, COUNT(*) as jumlah")
                ->where('jenis_berkas', $jenisBerkas)
                ->where('tahun', $tahun)
                ->where('kd_opd1', $skpd->kd_opd1)
                ->where('kd_opd2', $skpd->kd_opd2)
                ->where('kd_opd3', $skpd->kd_opd3)
                ->where('kd_opd4', $skpd->kd_opd4)
                ->where('kd_opd5', $skpd->kd_opd5)
                ->whereNotNull('diterima') // hanya yang sudah diterima/diverifikasi
                ->groupBy(DB::raw("EXTRACT(MONTH FROM tanggal_upload)"))
                ->get();

            // Set true untuk bulan yang ada datanya
            foreach ($laporanPerBulan as $item) {
                $bulanData[(int)$item->bulan] = true;
            }

            // Convert array keys to string for JSON compatibility
            $bulanDataString = [];
            foreach ($bulanData as $key => $value) {
                $bulanDataString[(string)$key] = $value;
            }

            $result[] = [
                'skpd' => $namaSkpd,
                'kd_opd1' => $skpd->kd_opd1,
                'kd_opd2' => $skpd->kd_opd2,
                'kd_opd3' => $skpd->kd_opd3,
                'kd_opd4' => $skpd->kd_opd4,
                'kd_opd5' => $skpd->kd_opd5,
                'bulan' => $bulanDataString,
            ];
        }

        return $result;
    }

    private function getNamaSkpd($kd_opd1, $kd_opd2, $kd_opd3, $kd_opd4, $kd_opd5)
    {
        $skpd = SKPDModel::where('kd_opd1', $kd_opd1)
            ->where('kd_opd2', $kd_opd2)
            ->where('kd_opd3', $kd_opd3)
            ->where('kd_opd4', $kd_opd4)
            ->where('kd_opd5', $kd_opd5)
            ->first();

        return $skpd ? $skpd->nm_opd : 'Unknown SKPD';
    }

    public function summary(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        
        // Filter berdasarkan kd_opd jika ada
        $kdOpd1 = $request->input('kd_opd1');
        $kdOpd2 = $request->input('kd_opd2');
        $kdOpd3 = $request->input('kd_opd3');
        $kdOpd4 = $request->input('kd_opd4');
        $kdOpd5 = $request->input('kd_opd5');

        // Helper function untuk apply filter
        $applyFilter = function($query) use ($kdOpd1, $kdOpd2, $kdOpd3, $kdOpd4, $kdOpd5) {
            if ($kdOpd1 !== null) $query->where('kd_opd1', $kdOpd1);
            if ($kdOpd2 !== null) $query->where('kd_opd2', $kdOpd2);
            if ($kdOpd3 !== null) $query->where('kd_opd3', $kdOpd3);
            if ($kdOpd4 !== null) $query->where('kd_opd4', $kdOpd4);
            if ($kdOpd5 !== null) $query->where('kd_opd5', $kdOpd5);
            return $query;
        };

        $summary = [
            'total_penerimaan' => $applyFilter(
                LaporanFungsionalModel::where('jenis_berkas', 'Penerimaan')
                    ->where('tahun', $tahun))->count(),
            
            'total_penerimaan_verifikasi' => $applyFilter(
                LaporanFungsionalModel::where('jenis_berkas', 'Penerimaan')
                    ->where('tahun', $tahun)
            )->whereNotNull('diterima')->count(),

            'total_pengeluaran' => $applyFilter(
                LaporanFungsionalModel::where('jenis_berkas', 'Pengeluaran')
                    ->where('tahun', $tahun))->count(),

            'total_pengeluaran_verifikasi' => $applyFilter(
                LaporanFungsionalModel::where('jenis_berkas', 'Pengeluaran')
                    ->where('tahun', $tahun)
            )->whereNotNull('diterima')->count(),

            'total_pending' => $applyFilter(
                LaporanFungsionalModel::where('tahun', $tahun)
            )->whereNull('diterima')->whereNull('ditolak')->count(),
            
            'total_ditolak' => $applyFilter(
                LaporanFungsionalModel::where('tahun', $tahun)
            )->whereNotNull('ditolak')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function getMonitoringData(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $dpaId = $request->input('dpa_id', null);
    
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        // ================= QUERY AKSES DPA =================
        $aksesQuery = AksesDPAModel::with('dpa')
            ->where('tahun', $tahun);
    
        // ================= FILTER DPA =================
        if ($dpaId && $dpaId !== 'all') {
            $aksesQuery->where('dpa_id', $dpaId);
        }
    
        // ================= FILTER OPD =================
        if ($kd_opd1) {
            $aksesQuery->where('kd_opd1', $kd_opd1);
        }
    
        if ($kd_opd2) {
            $aksesQuery->where('kd_opd2', $kd_opd2);
        }
    
        if ($kd_opd3) {
            $aksesQuery->where('kd_opd3', $kd_opd3);
        }
    
        if ($kd_opd4) {
            $aksesQuery->where('kd_opd4', $kd_opd4);
        }
    
        if ($kd_opd5) {
            $aksesQuery->where('kd_opd5', $kd_opd5);
        }
    
        $aksesData = $aksesQuery->get();
    
        // ================= INIT =================
        $monitoring = [];
    
        $summary = [
            'total' => 0,
            'uploaded' => 0,
            'notUploaded' => 0,
            'percentage' => 0,
        ];
    
        // ================= LOOP DATA =================
        foreach ($aksesData as $akses) {
    
            // 🔍 CEK LAPORAN
            $laporan = LaporanDPAModel::where([
                    'kd_opd1' => $akses->kd_opd1,
                    'kd_opd2' => $akses->kd_opd2,
                    'kd_opd3' => $akses->kd_opd3,
                    'kd_opd4' => $akses->kd_opd4,
                    'kd_opd5' => $akses->kd_opd5,
                    'dpa_id'  => $akses->dpa_id,
                    'tahun'   => $tahun,
                ])
                ->whereNull('deleted_at')
                ->latest('id')
                ->first();
    
            // 🏢 KEY SKPD
            $kdOpd = "{$akses->kd_opd1}.{$akses->kd_opd2}.{$akses->kd_opd3}.{$akses->kd_opd4}.{$akses->kd_opd5}";
    
            // 🏢 INIT SKPD
            if (!isset($monitoring[$kdOpd])) {
    
                $skpd = SKPDModel::where([
                    'kd_opd1' => $akses->kd_opd1,
                    'kd_opd2' => $akses->kd_opd2,
                    'kd_opd3' => $akses->kd_opd3,
                    'kd_opd4' => $akses->kd_opd4,
                    'kd_opd5' => $akses->kd_opd5,
                ])->first();
    
                $monitoring[$kdOpd] = [
                    'kd_opd'    => $kdOpd,
                    'nama_skpd' => $skpd->nm_opd ?? 'SKPD Tidak Ditemukan',
                    'items'     => [],
                ];
            }
    
            // 📌 STATUS UPLOAD
            $status = $laporan ? 'Sudah Upload' : 'Belum Upload';
    
            // 📌 STATUS PROSES
            $prosesStatus = null;
    
            if ($laporan) {
    
                if ($laporan->diterima) {
                    $prosesStatus = 'Berkas telah diverifikasi';
    
                } elseif ($laporan->ditolak) {
                    $prosesStatus = 'Berkas ditolak';
    
                } elseif ($laporan->proses) {
                    $prosesStatus = 'Berkas sedang diproses';
    
                } else {
                    $prosesStatus = 'Berkas terkirim';
                }
            }
    
            // 📥 PUSH ITEM
            $monitoring[$kdOpd]['items'][] = [
                'id'             => $laporan?->id,
                'dpa_id'         => $akses->dpa_id,
                'nama_dpa'       => $akses->dpa->nm_dpa ?? '-',
                'status'         => $status,
                'tanggal_upload' => $laporan?->created_at,
                'proses_status'  => $prosesStatus,
                'operator'       => $laporan?->nama_operator,
                'user_id'        => $laporan?->user_id,
            ];
        }
    
        // ================= SORTING & SUMMARY =================
        foreach ($monitoring as &$skpd) {
    
            usort($skpd['items'], function ($a, $b) {
                return strtotime($b['tanggal_upload'] ?? '1900-01-01')
                    <=> strtotime($a['tanggal_upload'] ?? '1900-01-01');
            });
    
            // 📊 SUMMARY BERDASARKAN ITEM PERTAMA
            $firstItem = $skpd['items'][0] ?? null;
    
            $summary['total']++;
    
            if ($firstItem && $firstItem['status'] === 'Sudah Upload') {
                $summary['uploaded']++;
            } else {
                $summary['notUploaded']++;
            }
        }
    
        $monitoring = array_values($monitoring);
    
        // ================= PERCENTAGE =================
        if ($summary['total'] > 0) {
    
            $summary['percentage'] = round(
                ($summary['uploaded'] / $summary['total']) * 100,
                2
            );
        }
    
        // ================= RESPONSE =================
        return response()->json([
            'success' => true,
            'data' => [
                'monitoring' => $monitoring,
                'summary' => $summary,
                'tahun' => $tahun,
            ]
        ]);
    }

    public function getAvailableYears()
    {
        $years = AksesDPAModel::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return response()->json([
            'success' => true,
            'data' => $years
        ]);
    }

    public function getDPATypes()
    {
        $dpaTypes = DPAModel::select('id', 'nm_dpa')
            ->whereNull('deleted_at')
            ->orderBy('nm_dpa')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dpaTypes
        ]);
    }

    public function getLaporanDetail($id)
    {
        $laporan = LaporanDPAModel::with(['dpa', 'user', 'operator'])
            ->find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        // Get SKPD info
        $skpd = SKPDModel::where('kd_opd1', $laporan->kd_opd1)
            ->where('kd_opd2', $laporan->kd_opd2)
            ->where('kd_opd3', $laporan->kd_opd3)
            ->where('kd_opd4', $laporan->kd_opd4)
            ->where('kd_opd5', $laporan->kd_opd5)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'laporan' => $laporan,
                'skpd' => $skpd,
            ]
        ]);
    }

    public function exportExcel(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $dpaId = $request->input('dpa_id', null);

        // Get monitoring data (reuse logic from getMonitoringData)
        $monitoringResponse = $this->getMonitoringData($request);
        $monitoring = $monitoringResponse->getData()->data->monitoring;

        // Setup headers for CSV export
        $filename = "monitoring_dpa_{$tahun}_" . date('YmdHis') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($monitoring) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, [
                'No',
                'Kode OPD',
                'Nama SKPD',
                'Jenis DPA',
                'Status Upload',
                'Tanggal Upload',
                'Status Proses',
                'Operator'
            ]);

            // Data rows
            foreach ($monitoring as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item->kd_opd,
                    $item->nama_skpd,
                    $item->nama_dpa,
                    $item->status,
                    $item->tanggal_upload ? date('d/m/Y H:i', strtotime($item->tanggal_upload)) : '-',
                    $item->proses_status ?? '-',
                    $item->operator ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStatisticsByDPA(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));

        $statistics = AksesDPAModel::with('dpa')
            ->where('tahun', $tahun)
            ->get()
            ->groupBy('dpa_id')
            ->map(function ($group) use ($tahun) {
                $dpaName = $group->first()->dpa->nm_dpa ?? 'Unknown';
                $total = $group->count();
                
                $uploaded = $group->filter(function ($akses) use ($tahun) {
                    return LaporanDPAModel::where('kd_opd1', $akses->kd_opd1)
                        ->where('kd_opd2', $akses->kd_opd2)
                        ->where('kd_opd3', $akses->kd_opd3)
                        ->where('kd_opd4', $akses->kd_opd4)
                        ->where('kd_opd5', $akses->kd_opd5)
                        ->where('dpa_id', $akses->dpa_id)
                        ->where('tahun', $tahun)
                        ->whereNull('deleted_at')
                        ->exists();
                })->count();

                return [
                    'name' => $dpaName,
                    'uploaded' => $uploaded,
                    'notUploaded' => $total - $uploaded,
                    'total' => $total,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    private function getDataBelanjaSKPD(
        $tahun,
        $kd_belanja1,
        $kd_belanja2,
        $kd_belanja3,
        $kd_opd1 = null,
        $kd_opd2 = null,
        $kd_opd3 = null,
        $kd_opd4 = null,
        $kd_opd5 = null
    ) {
    
        $sql = "
            SELECT 
                EXTRACT(MONTH FROM s.TANGGAL_UPLOAD) AS bulan,
                o.NM_OPD,
                s.kd_opd1,
                s.kd_opd2,
                s.kd_opd3,
                s.kd_opd4,
                s.kd_opd5,
                SUM(r.NILAI) AS total_belanja
            FROM SP2D s
            JOIN SP2D_REKENING r 
              ON s.ID_SP2D = r.SP2D_ID
            JOIN REF_OPD o
              ON TRIM(s.kd_opd1) = TRIM(o.kd_opd1)
             AND TRIM(s.kd_opd2) = TRIM(o.kd_opd2)
             AND TRIM(s.kd_opd3) = TRIM(o.kd_opd3)
             AND TRIM(s.kd_opd4) = TRIM(o.kd_opd4)
             AND TRIM(s.kd_opd5) = TRIM(o.kd_opd5)
            WHERE s.TAHUN = :tahun
              AND s.DITERIMA IS NOT NULL
              AND r.KD_REKENING1 = :k1
              AND r.KD_REKENING2 = :k2
              AND r.KD_REKENING3 = :k3
        ";
    
        $bindings = [
            'tahun' => $tahun,
            'k1' => $kd_belanja1,
            'k2' => $kd_belanja2,
            'k3' => $kd_belanja3,
        ];
    
        // =========================
        // FILTER OPD JIKA ADA
        // =========================
        if ($kd_opd1) {
            $sql .= " AND s.kd_opd1 = :kd_opd1 ";
            $bindings['kd_opd1'] = $kd_opd1;
        }
    
        if ($kd_opd2) {
            $sql .= " AND s.kd_opd2 = :kd_opd2 ";
            $bindings['kd_opd2'] = $kd_opd2;
        }
    
        if ($kd_opd3) {
            $sql .= " AND s.kd_opd3 = :kd_opd3 ";
            $bindings['kd_opd3'] = $kd_opd3;
        }
    
        if ($kd_opd4) {
            $sql .= " AND s.kd_opd4 = :kd_opd4 ";
            $bindings['kd_opd4'] = $kd_opd4;
        }
    
        if ($kd_opd5) {
            $sql .= " AND s.kd_opd5 = :kd_opd5 ";
            $bindings['kd_opd5'] = $kd_opd5;
        }
    
        $sql .= "
            GROUP BY 
                EXTRACT(MONTH FROM s.TANGGAL_UPLOAD),
                o.NM_OPD,
                s.kd_opd1,
                s.kd_opd2,
                s.kd_opd3,
                s.kd_opd4,
                s.kd_opd5
            ORDER BY o.NM_OPD
        ";
    
        $rows = DB::select($sql, $bindings);
    
        // 🔥 Transform ke format lama
        $result = [];
    
        foreach ($rows as $row) {
    
            $key = $row->kd_opd1 . '.' .
                   $row->kd_opd2 . '.' .
                   $row->kd_opd3 . '.' .
                   $row->kd_opd4 . '.' .
                   $row->kd_opd5;
    
            if (!isset($result[$key])) {
    
                $bulanData = array_fill(1, 12, false);
    
                $result[$key] = [
                    'skpd' => $row->nm_opd,
                    'kd_opd1' => $row->kd_opd1,
                    'kd_opd2' => $row->kd_opd2,
                    'kd_opd3' => $row->kd_opd3,
                    'kd_opd4' => $row->kd_opd4,
                    'kd_opd5' => $row->kd_opd5,
                    'bulan' => $bulanData,
                ];
            }
    
            $result[$key]['bulan'][(int)$row->bulan] = (float)$row->total_belanja;
        }
    
        // convert index bulan jadi string
        foreach ($result as &$item) {
    
            $bulanString = [];
    
            foreach ($item['bulan'] as $k => $v) {
                $bulanString[(string)$k] = $v;
            }
    
            $item['bulan'] = $bulanString;
        }
    
        return array_values($result);
    }
    
    public function getBelanjaSKPD(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
    
        // tahun list
        $currentYear = date('Y');
    
        $tahunList = [];
    
        for ($i = $currentYear - 3; $i <= $currentYear + 3; $i++) {
            $tahunList[] = (string)$i;
        }
    
        $kd_belanja1 = $request->kd_belanja1 ?? '5';
        $kd_belanja2 = $request->kd_belanja2 ?? '1';
        $kd_belanja3 = $request->kd_belanja3 ?? '01';
    
        // FILTER OPD
        $kd_opd1 = $request->kd_opd1;
        $kd_opd2 = $request->kd_opd2;
        $kd_opd3 = $request->kd_opd3;
        $kd_opd4 = $request->kd_opd4;
        $kd_opd5 = $request->kd_opd5;
    
        $dataMasuk = $this->getDataBelanjaSKPD(
            $tahun,
            $kd_belanja1,
            $kd_belanja2,
            $kd_belanja3,
            $kd_opd1,
            $kd_opd2,
            $kd_opd3,
            $kd_opd4,
            $kd_opd5
        );
    
        return response()->json([
            'success' => true,
            'data' => [
                'tahun_list' => $tahunList,
                'tahun_selected' => $tahun,
                'belanja' => $dataMasuk,
            ]
        ]);
    }
}