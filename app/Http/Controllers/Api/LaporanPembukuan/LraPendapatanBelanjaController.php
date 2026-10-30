<?php

namespace App\Http\Controllers\Api\LaporanPembukuan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\ResolvesFilterMeta;

class LraPendapatanBelanjaController extends Controller
{
    use ResolvesFilterMeta;
    public function index(Request $request)
    {
        $tglParam = $request->input('tanggal');
        $fromParam = $request->input('range_tanggal.from') 
            ?? $request->input('from') 
            ?? $request->input('tgl_awal');
        $toParam = $request->input('range_tanggal.to') 
            ?? $request->input('to') 
            ?? $request->input('tgl_akhir') 
            ?? $tglParam;

        $tglAwal = !empty($fromParam) 
            ? Carbon::parse($fromParam)->format('Y-m-d') 
            : (!empty($tglParam) ? Carbon::parse($tglParam)->startOfYear()->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d'));

        $tglAkhir = !empty($toParam) 
            ? Carbon::parse($toParam)->format('Y-m-d') 
            : (!empty($tglParam) ? Carbon::parse($tglParam)->format('Y-m-d') : Carbon::now()->format('Y-m-d'));

        $tahun = Carbon::parse($tglAkhir)->year;
        $startOfYear = Carbon::parse($tahun . '-01-01')->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | 1. DATA PENDAPATAN (AKUN 4) DARI PAGU_SUMBER_DANA
        |--------------------------------------------------------------------------
        */
        $pendapatanRows = DB::connection('oracle')
            ->table('PAGU_SUMBER_DANA as psd')
            ->leftJoin('REF_SUMBER_DANA as rsd', function ($j) {
                $j->on(DB::raw("TRIM(rsd.KD_REF1)"), '=', DB::raw("TRIM(psd.KD_REF1)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF2)"), '=', DB::raw("TRIM(psd.KD_REF2)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF3)"), '=', DB::raw("TRIM(psd.KD_REF3)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF4)"), '=', DB::raw("TRIM(psd.KD_REF4)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF5)"), '=', DB::raw("TRIM(psd.KD_REF5)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF6)"), '=', DB::raw("TRIM(psd.KD_REF6)"));
            })
            ->where(DB::raw("TRIM(psd.TAHUN)"), $tahun)
            ->where(DB::raw("TRIM(psd.KD_REF1)"), '4')
            ->select([
                DB::raw("TRIM(psd.KD_REF1) as KD_AKUN"),
                DB::raw("TRIM(psd.KD_REF1)||'.'||TRIM(psd.KD_REF2) as KD_KELOMPOK"),
                DB::raw("TRIM(psd.KD_REF1)||'.'||TRIM(psd.KD_REF2)||'.'||TRIM(psd.KD_REF3) as KD_JENIS"),
                DB::raw("TRIM(rsd.NM_REF) as NM_REF"),
                DB::raw("SUM(psd.PAGU) as ANGGARAN")
            ])
            ->groupBy('psd.KD_REF1', 'psd.KD_REF2', 'psd.KD_REF3', 'rsd.NM_REF')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. DATA BELANJA (AKUN 5): ANGGARAN DARI PAGU_BELANJA
        |--------------------------------------------------------------------------
        */
        $paguBelanjaQuery = DB::connection('oracle')
            ->table('PAGU_BELANJA as pb')
            ->leftJoin('REF_JENIS_BELANJA as rjb', function ($j) {
                $j->on(DB::raw("TRIM(rjb.KD_REF1)"), '=', DB::raw("TRIM(pb.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF2)"), '=', DB::raw("TRIM(pb.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF3)"), '=', DB::raw("TRIM(pb.KD_REKENING3)"));
            })
            ->where('pb.IS_DELETED', 0)
            ->where(DB::raw("TRIM(pb.TAHUN_REK)"), $tahun)
            ->where(DB::raw("TRIM(pb.KD_REKENING1)"), '5');

        // Apply filters
        $opd = $request->skpd;
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $paguBelanjaQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }
        $rawUrusan = $request->urusan;
        $filterUrusan = is_array($rawUrusan) ? trim($rawUrusan['kd_urusan'] ?? '') : trim((string)$rawUrusan);
        if (!empty($filterUrusan)) {
            $paguBelanjaQuery->where(DB::raw('TRIM(pb.KD_URUSAN)'), $filterUrusan);
        }
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $paguBelanjaQuery->where(DB::raw('TRIM(pb.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $paguBelanjaQuery->where(DB::raw('TRIM(pb.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }
        $program = $request->program;
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $paguBelanjaQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }
        $kegiatan = $request->kegiatan;
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $paguBelanjaQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }
        $sub = $request->subkegiatan;
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $paguBelanjaQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        $paguBelanjaRows = $paguBelanjaQuery
            ->select([
                DB::raw("TRIM(pb.KD_REKENING1) as KD_AKUN"),
                DB::raw("TRIM(pb.KD_REKENING1)||'.'||TRIM(pb.KD_REKENING2) as KD_KELOMPOK"),
                DB::raw("TRIM(pb.KD_REKENING1)||'.'||TRIM(pb.KD_REKENING2)||'.'||TRIM(pb.KD_REKENING3) as KD_JENIS"),
                DB::raw("TRIM(rjb.NM_BELANJA) as NM_JENIS"),
                DB::raw("SUM(pb.JUMLAH_PAGU) as ANGGARAN")
            ])
            ->groupBy('pb.KD_REKENING1', 'pb.KD_REKENING2', 'pb.KD_REKENING3', 'rjb.NM_BELANJA')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. DATA BELANJA (AKUN 5): REALISASI DARI SP2D_REKENING + SP2D
        |--------------------------------------------------------------------------
        */
        $realisasiBelanjaQuery = DB::connection('oracle')
            ->table('SP2D_REKENING as r')
            ->join('SP2D as s', 's.ID_SP2D', '=', 'r.SP2D_ID')
            ->leftJoin('REF_JENIS_BELANJA as rjb', function ($j) {
                $j->on(DB::raw("TRIM(rjb.KD_REF1)"), '=', DB::raw("TRIM(r.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF2)"), '=', DB::raw("TRIM(r.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF3)"), '=', DB::raw("TRIM(r.KD_REKENING3)"));
            })
            ->where(DB::raw("TRIM(r.KD_REKENING1)"), '5')
            ->whereBetween(
                DB::raw('TRUNC(s.TANGGAL_UPLOAD)'),
                [
                    DB::raw("TO_DATE('{$startOfYear}','YYYY-MM-DD')"),
                    DB::raw("TO_DATE('{$tglAkhir}','YYYY-MM-DD')")
                ]
            );

        // Apply filters
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $realisasiBelanjaQuery->where(DB::raw("TRIM(s." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }
        if (!empty($filterUrusan)) {
            $realisasiBelanjaQuery->where(DB::raw('TRIM(r.KD_URUSAN)'), $filterUrusan);
        }
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $realisasiBelanjaQuery->where(DB::raw('TRIM(r.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $realisasiBelanjaQuery->where(DB::raw('TRIM(r.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $realisasiBelanjaQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $realisasiBelanjaQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $realisasiBelanjaQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        $realisasiBelanjaRows = $realisasiBelanjaQuery
            ->select([
                DB::raw("TRIM(r.KD_REKENING1) as KD_AKUN"),
                DB::raw("TRIM(r.KD_REKENING1)||'.'||TRIM(r.KD_REKENING2) as KD_KELOMPOK"),
                DB::raw("TRIM(r.KD_REKENING1)||'.'||TRIM(r.KD_REKENING2)||'.'||TRIM(r.KD_REKENING3) as KD_JENIS"),
                DB::raw("TRIM(rjb.NM_BELANJA) as NM_JENIS"),
                DB::raw("SUM(CASE WHEN TRUNC(s.TANGGAL_UPLOAD) < TO_DATE('{$tglAwal}', 'YYYY-MM-DD') THEN r.NILAI ELSE 0 END) AS REALISASI_LALU"),
                DB::raw("SUM(CASE WHEN TRUNC(s.TANGGAL_UPLOAD) >= TO_DATE('{$tglAwal}', 'YYYY-MM-DD') AND TRUNC(s.TANGGAL_UPLOAD) <= TO_DATE('{$tglAkhir}', 'YYYY-MM-DD') THEN r.NILAI ELSE 0 END) AS REALISASI_INI"),
                DB::raw("SUM(r.NILAI) AS REALISASI_TOTAL")
            ])
            ->groupBy('r.KD_REKENING1', 'r.KD_REKENING2', 'r.KD_REKENING3', 'rjb.NM_BELANJA')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. DATA PEMBIAYAAN (AKUN 6) DARI PAGU_SUMBER_DANA
        |--------------------------------------------------------------------------
        */
        $pembiayaanRows = DB::connection('oracle')
            ->table('PAGU_SUMBER_DANA as psd')
            ->leftJoin('REF_SUMBER_DANA as rsd', function ($j) {
                $j->on(DB::raw("TRIM(rsd.KD_REF1)"), '=', DB::raw("TRIM(psd.KD_REF1)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF2)"), '=', DB::raw("TRIM(psd.KD_REF2)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF3)"), '=', DB::raw("TRIM(psd.KD_REF3)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF4)"), '=', DB::raw("TRIM(psd.KD_REF4)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF5)"), '=', DB::raw("TRIM(psd.KD_REF5)"))
                  ->on(DB::raw("TRIM(rsd.KD_REF6)"), '=', DB::raw("TRIM(psd.KD_REF6)"));
            })
            ->where(DB::raw("TRIM(psd.TAHUN)"), $tahun)
            ->where(DB::raw("TRIM(psd.KD_REF1)"), '6')
            ->select([
                DB::raw("TRIM(psd.KD_REF1) as KD_AKUN"),
                DB::raw("TRIM(psd.KD_REF1)||'.'||TRIM(psd.KD_REF2) as KD_KELOMPOK"),
                DB::raw("TRIM(psd.KD_REF1)||'.'||TRIM(psd.KD_REF2)||'.'||TRIM(psd.KD_REF3) as KD_JENIS"),
                DB::raw("TRIM(rsd.NM_REF) as NM_REF"),
                DB::raw("SUM(psd.PAGU) as ANGGARAN")
            ])
            ->groupBy('psd.KD_REF1', 'psd.KD_REF2', 'psd.KD_REF3', 'rsd.NM_REF')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 5. STRUKTURISASI DATA LAPORAN
        |--------------------------------------------------------------------------
        */
        $namaKelompok = [
            '4.1' => 'PENDAPATAN ASLI DAERAH (PAD)',
            '4.2' => 'PENDAPATAN TRANSFER',
            '4.3' => 'LAIN-LAIN PENDAPATAN DAERAH YANG SAH',
            '5.1' => 'BELANJA OPERASI',
            '5.2' => 'BELANJA MODAL',
            '5.3' => 'BELANJA TIDAK TERDUGA',
            '5.4' => 'BELANJA TRANSFER',
            '6.1' => 'PENERIMAAN PEMBIAYAAN',
            '6.2' => 'PENGELUARAN PEMBIAYAAN',
        ];

        // Group Pendapatan
        $pendapatanData = [];
        foreach ($pendapatanRows as $r) {
            $kel = $r->kd_kelompok ?? $r->KD_KELOMPOK;
            $jenis = $r->kd_jenis ?? $r->KD_JENIS;
            $nama = $r->nm_ref ?? $r->NM_REF ?? "PENDAPATAN $jenis";
            $anggaran = (float) ($r->anggaran ?? $r->ANGGARAN ?? 0);

            if ($anggaran == 0) continue;

            if (!isset($pendapatanData[$kel])) {
                $pendapatanData[$kel] = [
                    'kode' => $kel,
                    'nama' => $namaKelompok[$kel] ?? "PENDAPATAN $kel",
                    'items' => []
                ];
            }
            $pendapatanData[$kel]['items'][] = [
                'kode' => $jenis,
                'nama' => $nama,
                'anggaran' => $anggaran,
                'realisasi_lalu' => 0,
                'realisasi_ini' => 0,
                'realisasi_total' => 0,
                'sisa' => $anggaran,
                'persen' => 0
            ];
        }

        // Group Belanja
        $belanjaMap = [];
        foreach ($paguBelanjaRows as $r) {
            $kel = $r->kd_kelompok ?? $r->KD_KELOMPOK;
            $jenis = $r->kd_jenis ?? $r->KD_JENIS;
            $nama = $r->nm_jenis ?? $r->NM_JENIS ?? "BELANJA $jenis";

            if (!isset($belanjaMap[$kel])) {
                $belanjaMap[$kel] = [
                    'kode' => $kel,
                    'nama' => $namaKelompok[$kel] ?? "BELANJA $kel",
                    'items' => []
                ];
            }
            if (!isset($belanjaMap[$kel]['items'][$jenis])) {
                $belanjaMap[$kel]['items'][$jenis] = [
                    'kode' => $jenis,
                    'nama' => $nama,
                    'anggaran' => 0,
                    'realisasi_lalu' => 0,
                    'realisasi_ini' => 0,
                    'realisasi_total' => 0,
                    'sisa' => 0,
                    'persen' => 0
                ];
            }
            $belanjaMap[$kel]['items'][$jenis]['anggaran'] += (float) ($r->anggaran ?? $r->ANGGARAN ?? 0);
        }

        foreach ($realisasiBelanjaRows as $r) {
            $kel = $r->kd_kelompok ?? $r->KD_KELOMPOK;
            $jenis = $r->kd_jenis ?? $r->KD_JENIS;
            $nama = $r->nm_jenis ?? $r->NM_JENIS ?? "BELANJA $jenis";

            if (!isset($belanjaMap[$kel])) {
                $belanjaMap[$kel] = [
                    'kode' => $kel,
                    'nama' => $namaKelompok[$kel] ?? "BELANJA $kel",
                    'items' => []
                ];
            }
            if (!isset($belanjaMap[$kel]['items'][$jenis])) {
                $belanjaMap[$kel]['items'][$jenis] = [
                    'kode' => $jenis,
                    'nama' => $nama,
                    'anggaran' => 0,
                    'realisasi_lalu' => 0,
                    'realisasi_ini' => 0,
                    'realisasi_total' => 0,
                    'sisa' => 0,
                    'persen' => 0
                ];
            }
            $lalu = (float) ($r->realisasi_lalu ?? $r->REALISASI_LALU ?? 0);
            $ini = (float) ($r->realisasi_ini ?? $r->REALISASI_INI ?? 0);
            $total = (float) ($r->realisasi_total ?? $r->REALISASI_TOTAL ?? ($lalu + $ini));

            $belanjaMap[$kel]['items'][$jenis]['realisasi_lalu'] += $lalu;
            $belanjaMap[$kel]['items'][$jenis]['realisasi_ini'] += $ini;
            $belanjaMap[$kel]['items'][$jenis]['realisasi_total'] += $total;
        }

        // Filter Belanja yang terpakai
        $belanjaData = collect($belanjaMap)->map(function ($k) {
            $items = collect($k['items'])->filter(function ($i) {
                return ((float)$i['anggaran'] != 0) || ((float)$i['realisasi_total'] != 0);
            })->map(function ($i) {
                $i['sisa'] = (float)$i['anggaran'] - (float)$i['realisasi_total'];
                $i['persen'] = (float)$i['anggaran'] > 0 ? ((float)$i['realisasi_total'] / (float)$i['anggaran']) * 100 : 0;
                return $i;
            })->sortBy('kode')->values();

            $totAnggaran = $items->sum('anggaran');
            $totLalu = $items->sum('realisasi_lalu');
            $totIni = $items->sum('realisasi_ini');
            $totSampai = $items->sum('realisasi_total');
            $totSisa = $totAnggaran - $totSampai;
            $persen = $totAnggaran > 0 ? ($totSampai / $totAnggaran) * 100 : 0;

            return [
                'kode' => $k['kode'],
                'nama' => $k['nama'],
                'total_anggaran' => $totAnggaran,
                'total_lalu' => $totLalu,
                'total_ini' => $totIni,
                'total_sampai_ini' => $totSampai,
                'total_sisa' => $totSisa,
                'persen' => $persen,
                'items' => $items->all()
            ];
        })->filter(function ($k) {
            return count($k['items']) > 0;
        })->sortBy('kode')->values();

        // Group Pembiayaan
        $pembiayaanData = [];
        foreach ($pembiayaanRows as $r) {
            $kel = $r->kd_kelompok ?? $r->KD_KELOMPOK;
            $jenis = $r->kd_jenis ?? $r->KD_JENIS;
            $nama = $r->nm_ref ?? $r->NM_REF ?? "PEMBIAYAAN $jenis";
            $anggaran = (float) ($r->anggaran ?? $r->ANGGARAN ?? 0);

            if ($anggaran == 0) continue;

            if (!isset($pembiayaanData[$kel])) {
                $pembiayaanData[$kel] = [
                    'kode' => $kel,
                    'nama' => $namaKelompok[$kel] ?? "PEMBIAYAAN $kel",
                    'items' => []
                ];
            }
            $pembiayaanData[$kel]['items'][] = [
                'kode' => $jenis,
                'nama' => $nama,
                'anggaran' => $anggaran,
                'realisasi_lalu' => 0,
                'realisasi_ini' => 0,
                'realisasi_total' => 0,
                'sisa' => $anggaran,
                'persen' => 0
            ];
        }

        $pdf = Pdf::loadView(
            'laporan_pembukuan.lra_pendapatan_belanja.cetak_pdf',
            [
                'tanggal'    => $tglAkhir,
                'from'       => $tglAwal,
                'tahun'      => $tahun,
                'skpd'       => $request->skpd,
                'meta'       => $this->resolveFilterMeta($request),
                'pendapatan' => $pendapatanData,
                'belanja'    => $belanjaData,
                'pembiayaan' => $pembiayaanData,
            ]
        );

        return $pdf
            ->setPaper('A4', 'landscape')
            ->stream('LRA_PENDAPATAN_BELANJA.pdf');
    }
}