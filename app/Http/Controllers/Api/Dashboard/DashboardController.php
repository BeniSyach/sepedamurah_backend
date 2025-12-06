<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
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
        $from = $request->from;
        $to   = $request->to;
    
        // ==============================================================================================
        // TOTAL GABUNGAN (PAKAI TANGGAL UPLOAD)
        // ==============================================================================================
        $permohonanSPD = DB::table('permohonan_spd')->whereNull('deleted_at');
        $sp2dKirim     = DB::table('spd_terkirim')->whereNull('deleted_at');
    
        if ($from && $to) {
            $permohonanSPD->whereBetween('tanggal_upload', [$from, $to]);
            $sp2dKirim->whereBetween('tanggal_upload', [$from, $to]);
        }
    
        $totalSPDGabungan = $permohonanSPD
            ->select('id')
            ->union(
                $sp2dKirim->select('id_berkas as id')
            )
            ->distinct()
            ->count();
    
        // ==============================================================================================
        // SPD TERKAIT MODEL
        // ==============================================================================================
        // Terverifikasi (pakai tanggal diterima)
        $spdTerverifikasi = PermohonanSPDModel::whereNotNull('diterima');
        if ($from && $to) {
            $spdTerverifikasi->whereBetween('diterima', [$from, $to]);
        }
        $totalSPDTerverifikasi = $spdTerverifikasi->count();
    
        // Ditolak (pakai tanggal ditolak)
        $spdDitolak = PermohonanSPDModel::whereNotNull('ditolak');
        if ($from && $to) {
            $spdDitolak->whereBetween('ditolak', [$from, $to]);
        }
        $totalSPDDitolak = $spdDitolak->count();
    
        // TTE (pakai tgl_tte)
        $spdTTE = SPDTerkirimModel::whereNotNull('tgl_tte');
        if ($from && $to) {
            $spdTTE->whereBetween('tgl_tte', [$from, $to]);
        }
        $totalSPDTTE = $spdTTE->count();
    
        // ==============================================================================================
        // SP2D
        // ==============================================================================================
        // Total permohonan SP2D (pakai tanggal upload)
        $sp2dPermohonan = SP2DModel::query();
        if ($from && $to) {
            $sp2dPermohonan->whereBetween('tanggal_upload', [$from, $to]);
        }
        $totalSP2DPermohonan = $sp2dPermohonan->count();
    
        // SP2D Terverifikasi (pakai tanggal diterima)
        $sp2dTerverifikasi = SP2DModel::whereNotNull('diterima');
        if ($from && $to) {
            $sp2dTerverifikasi->whereBetween('diterima', [$from, $to]);
        }
        $totalSP2DTerverifikasi = $sp2dTerverifikasi->count();
    
        // SP2D Ditolak (pakai tanggal ditolak)
        $sp2dDitolak = SP2DModel::whereNotNull('ditolak');
        if ($from && $to) {
            $sp2dDitolak->whereBetween('ditolak', [$from, $to]);
        }
        $totalSP2DDitolak = $sp2dDitolak->count();
    
        // SP2D TTE (pakai tgl_tte)
        $sp2dTTE = SP2DKirimModel::whereNotNull('tgl_tte');
        if ($from && $to) {
            $sp2dTTE->whereBetween('tgl_tte', [$from, $to]);
        }
        $totalSP2DTTE = $sp2dTTE->count();
    
        return response()->json([
            'status' => true,
            'data' => [
                'total_permohonan_spd' => $totalSPDGabungan,
                'total_spd_terverifikasi' => $totalSPDTerverifikasi,
                'total_spd_ditolak' => $totalSPDDitolak,
                'total_spd_tte' => $totalSPDTTE,
                'total_permohonan_sp2d' => $totalSP2DPermohonan,
                'total_sp2d_terverifikasi' => $totalSP2DTerverifikasi,
                'total_sp2d_ditolak' => $totalSP2DDitolak,
                'total_sp2d_tte' => $totalSP2DTTE,
            ]
        ]);
    }
    
    public function berkas_masuk_sp2d()
    {
        $perPage     = 5;
        $orderColumn = 'sp2d.tanggal_upload'; // gunakan prefix biar aman
        $orderDir    = 'desc';
    
        // ==========================
        // QUERY DASAR
        // ==========================
        $query = Sp2dModel::query()
            ->with(['rekening', 'sumberDana', 'sp2dkirim'])
            ->whereNull('sp2d.deleted_at')
            ->join('ref_opd', function ($join) {
                $join->on('sp2d.kd_opd1', '=', 'ref_opd.kd_opd1')
                    ->on('sp2d.kd_opd2', '=', 'ref_opd.kd_opd2')
                    ->on('sp2d.kd_opd3', '=', 'ref_opd.kd_opd3')
                    ->on('sp2d.kd_opd4', '=', 'ref_opd.kd_opd4')
                    ->on('sp2d.kd_opd5', '=', 'ref_opd.kd_opd5');
            })
            ->select('sp2d.*', 'ref_opd.nm_opd');
    
        // ==========================
        // FILTER BERKAS MASUK
        // ==========================
        // belum diproses
        $query->whereNull('sp2d.proses');
    
        // belum diverifikasi
        $query->whereNull('sp2d.diterima')
              ->whereNull('sp2d.ditolak');
    
        // ==========================
        // PAGINATION & ORDER
        // ==========================
        $data = $query->orderBy($orderColumn, $orderDir)->paginate($perPage);
    
        // ==========================
        // TRANSFORMASI DATA
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
        // RETURN JSON
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
        $tahun = $request->tahun ?? date('Y'); // default tahun berjalan

        $data = Sp2dModel::select(
                DB::raw("TO_CHAR(tanggal_upload, 'MM') AS bulan"),
                DB::raw("COUNT(*) AS total")
            )
            ->whereRaw("EXTRACT(YEAR FROM tanggal_upload) = ?", [$tahun])
            ->groupBy(DB::raw("TO_CHAR(tanggal_upload, 'MM')"))
            ->orderBy(DB::raw("TO_CHAR(tanggal_upload, 'MM')"))
            ->get();

        // Format agar lebih enak ke Chart.js
        $result = [
            'labels' => [],
            'values' => [],
        ];

        $namaBulan = [
            '01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
            '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'
        ];

        foreach ($data as $row) {
            $result['labels'][] = $namaBulan[$row->bulan];
            $result['values'][] = (int)$row->total;
        }

        return response()->json([
            'status' => true,
            'tahun' => $tahun,
            'chart' => $result,
            'raw' => $data
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
}