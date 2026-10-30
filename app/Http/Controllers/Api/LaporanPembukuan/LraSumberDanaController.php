<?php

namespace App\Http\Controllers\Api\LaporanPembukuan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\ResolvesFilterMeta;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\BuildsHierarchicalLra;

class LraSumberDanaController extends Controller
{
    use ResolvesFilterMeta, BuildsHierarchicalLra;

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
        | 1. QUERY REALISASI DARI VIEW_LRA_SUMBER_DANA
        |--------------------------------------------------------------------------
        */
        $realisasiQuery = DB::connection('oracle')
            ->table('VIEW_LRA_SUMBER_DANA');

        // Filter Urusan
        $rawUrusan = $request->urusan;
        $filterUrusan = is_array($rawUrusan) ? trim($rawUrusan['kd_urusan'] ?? '') : trim((string)$rawUrusan);
        if (!empty($filterUrusan)) {
            $realisasiQuery->where(DB::raw('TRIM(KD_URUSAN)'), $filterUrusan);
        }

        // Filter Bidang Urusan
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $realisasiQuery->where('KD_BU1', $request->bidang_urusan['kd_bu1']);
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $realisasiQuery->where('KD_BU2', $request->bidang_urusan['kd_bu2']);
        }

        // Filter SKPD
        $opd = $request->skpd;
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(" . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }

        // Filter Program
        $program = $request->program;
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(" . strtoupper($field) . ")"), trim($program[$field]));
            }
        }

        // Filter Kegiatan
        $kegiatan = $request->kegiatan;
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(" . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }

        // Filter Sub Kegiatan
        $sub = $request->subkegiatan;
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(" . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        // Filter Sumber Dana
        $sd = $request->sumber_dana;
        foreach (['kd_ref1', 'kd_ref2', 'kd_ref3', 'kd_ref4', 'kd_ref5', 'kd_ref6'] as $field) {
            if (!empty($sd[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(" . strtoupper($field) . ")"), trim($sd[$field]));
            }
        }

        // Filter Rekening
        if (!empty($request->rek_akun)) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING1)'), trim($request->rek_akun));
        }
        if (!empty($request->rek_kelompok['kd_kel1'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING1)'), trim($request->rek_kelompok['kd_kel1']));
        }
        if (!empty($request->rek_kelompok['kd_kel2'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING2)'), trim($request->rek_kelompok['kd_kel2']));
        }
        if (!empty($request->rek_jenis['kd_jenis1'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING1)'), trim($request->rek_jenis['kd_jenis1']));
        }
        if (!empty($request->rek_jenis['kd_jenis2'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING2)'), trim($request->rek_jenis['kd_jenis2']));
        }
        if (!empty($request->rek_jenis['kd_jenis3'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING3)'), trim($request->rek_jenis['kd_jenis3']));
        }
        if (!empty($request->rek_objek['kd_objek4'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING4)'), trim($request->rek_objek['kd_objek4']));
        }
        if (!empty($request->rek_rincian['kd_rincian5'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING5)'), trim($request->rek_rincian['kd_rincian5']));
        }
        if (!empty($request->sub_rincian['kd_subrincian6'])) {
            $realisasiQuery->where(DB::raw('TRIM(KD_REKENING6)'), trim($request->sub_rincian['kd_subrincian6']));
        }

        // Filter Tanggal Realisasi (dari awal tahun s/d tglAkhir)
        $realisasiQuery->whereBetween(
            DB::raw('TRUNC(TANGGAL)'),
            [
                DB::raw("TO_DATE('{$startOfYear}','YYYY-MM-DD')"),
                DB::raw("TO_DATE('{$tglAkhir}','YYYY-MM-DD')")
            ]
        );

        $realisasiRows = $realisasiQuery
            ->select([
                'KODE_SUMBER_DANA',
                'NM_SUMBER_DANA',
                'KODE_BELANJA',
                'NM_BELANJA',
                DB::raw("TRIM(KD_URUSAN) as KD_URUSAN"),
                'NM_URUSAN',
                DB::raw("TRIM(KODE_BIDANG_URUSAN) as KD_BU"),
                'NM_BU',
                DB::raw("TRIM(KODE_PROGRAM) as KD_PROG"),
                'NM_PROGRAM',
                DB::raw("TRIM(KODE_KEGIATAN) as KD_KEG"),
                'NM_KEGIATAN',
                DB::raw("TRIM(KODE_SUBKEGIATAN) as KD_SUB"),
                'NM_SUBKEGIATAN',
                DB::raw("SUM(CASE WHEN TRUNC(TANGGAL) < TO_DATE('{$tglAwal}', 'YYYY-MM-DD') THEN NILAI ELSE 0 END) AS REALISASI_LALU"),
                DB::raw("SUM(CASE WHEN TRUNC(TANGGAL) >= TO_DATE('{$tglAwal}', 'YYYY-MM-DD') AND TRUNC(TANGGAL) <= TO_DATE('{$tglAkhir}', 'YYYY-MM-DD') THEN NILAI ELSE 0 END) AS REALISASI_INI"),
                DB::raw("SUM(NILAI) AS REALISASI_TOTAL")
            ])
            ->groupBy(
                'KODE_SUMBER_DANA', 'NM_SUMBER_DANA',
                'KODE_BELANJA', 'NM_BELANJA',
                'KD_URUSAN', 'NM_URUSAN',
                'KODE_BIDANG_URUSAN', 'NM_BU',
                'KODE_PROGRAM', 'NM_PROGRAM',
                'KODE_KEGIATAN', 'NM_KEGIATAN',
                'KODE_SUBKEGIATAN', 'NM_SUBKEGIATAN'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. QUERY ANGGARAN DARI PAGU_BELANJA (MAPPING REF_SUMBER_DANA)
        |--------------------------------------------------------------------------
        */
        $paguQuery = DB::connection('oracle')
            ->table('PAGU_BELANJA as pb')
            ->join('REF_SUMBER_DANA as rsd', function ($join) {
                $join->on(DB::raw("TRIM(rsd.KD_PAGU_SD1)"), '=', DB::raw("TRIM(pb.KD_PAGU_SD1)"))
                     ->on(DB::raw("TRIM(rsd.KD_PAGU_SD2)"), '=', DB::raw("TRIM(pb.KD_PAGU_SD2)"))
                     ->on(DB::raw("TRIM(rsd.KD_PAGU_SD3)"), '=', DB::raw("TRIM(pb.KD_PAGU_SD3)"))
                     ->on(DB::raw("TRIM(rsd.KD_PAGU_SD4)"), '=', DB::raw("TRIM(pb.KD_PAGU_SD4)"))
                     ->on(DB::raw("NVL(TRIM(rsd.KD_PAGU_SD5), '-')"), '=', DB::raw("NVL(TRIM(pb.KD_PAGU_SD5), '-')"))
                     ->on(DB::raw("NVL(TRIM(rsd.KD_PAGU_SD6), '-')"), '=', DB::raw("NVL(TRIM(pb.KD_PAGU_SD6), '-')"));
            })
            ->leftJoin('REF_JENIS_BELANJA as rjb', function ($join) {
                $join->on(DB::raw("TRIM(rjb.KD_REF1)"), '=', DB::raw("TRIM(pb.KD_REKENING1)"))
                     ->on(DB::raw("TRIM(rjb.KD_REF2)"), '=', DB::raw("TRIM(pb.KD_REKENING2)"))
                     ->on(DB::raw("TRIM(rjb.KD_REF3)"), '=', DB::raw("TRIM(pb.KD_REKENING3)"));
            })
            ->leftJoin('REF_URUSAN as u', DB::raw("TRIM(u.KD_URUSAN)"), '=', DB::raw("TRIM(pb.KD_URUSAN)"))
            ->leftJoin('REF_BIDANG_URUSAN as bu', function ($j) {
                $j->on(DB::raw("TRIM(bu.KD_BU1)"), '=', DB::raw("TRIM(pb.KD_BU1)"))
                  ->on(DB::raw("TRIM(bu.KD_BU2)"), '=', DB::raw("TRIM(pb.KD_BU2)"));
            })
            ->leftJoin('REF_PROGRAM as rp', function ($j) {
                $j->on(DB::raw("TRIM(rp.KD_PROG1)"), '=', DB::raw("TRIM(pb.KD_PROG1)"))
                  ->on(DB::raw("TRIM(rp.KD_PROG2)"), '=', DB::raw("TRIM(pb.KD_PROG2)"))
                  ->on(DB::raw("TRIM(rp.KD_PROG3)"), '=', DB::raw("TRIM(pb.KD_PROG3)"));
            })
            ->leftJoin('REF_KEGIATAN as rk', function ($j) {
                $j->on(DB::raw("TRIM(rk.KD_KEG1)"), '=', DB::raw("TRIM(pb.KD_KEG1)"))
                  ->on(DB::raw("TRIM(rk.KD_KEG2)"), '=', DB::raw("TRIM(pb.KD_KEG2)"))
                  ->on(DB::raw("TRIM(rk.KD_KEG3)"), '=', DB::raw("TRIM(pb.KD_KEG3)"))
                  ->on(DB::raw("TRIM(rk.KD_KEG4)"), '=', DB::raw("TRIM(pb.KD_KEG4)"))
                  ->on(DB::raw("TRIM(rk.KD_KEG5)"), '=', DB::raw("TRIM(pb.KD_KEG5)"));
            })
            ->leftJoin('REF_SUBKEGIATAN as rs', function ($j) {
                $j->on(DB::raw("TRIM(rs.KD_SUBKEG1)"), '=', DB::raw("TRIM(pb.KD_SUBKEG1)"))
                  ->on(DB::raw("TRIM(rs.KD_SUBKEG2)"), '=', DB::raw("TRIM(pb.KD_SUBKEG2)"))
                  ->on(DB::raw("TRIM(rs.KD_SUBKEG3)"), '=', DB::raw("TRIM(pb.KD_SUBKEG3)"))
                  ->on(DB::raw("TRIM(rs.KD_SUBKEG4)"), '=', DB::raw("TRIM(pb.KD_SUBKEG4)"))
                  ->on(DB::raw("TRIM(rs.KD_SUBKEG5)"), '=', DB::raw("TRIM(pb.KD_SUBKEG5)"))
                  ->on(DB::raw("TRIM(rs.KD_SUBKEG6)"), '=', DB::raw("TRIM(pb.KD_SUBKEG6)"));
            })
            ->where('pb.IS_DELETED', 0)
            ->whereNotNull('pb.KD_PAGU_SD1')
            ->whereRaw("TRIM(pb.KD_PAGU_SD1) != ''")
            ->whereNotNull('rsd.KD_PAGU_SD1')
            ->where('rsd.STATUS', '1')
            ->where(DB::raw("TRIM(pb.TAHUN_REK)"), $tahun);

        // Filter Urusan Pagu
        if (!empty($filterUrusan)) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_URUSAN)'), $filterUrusan);
        }

        // Filter Bidang Urusan Pagu
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }

        // Filter SKPD Pagu
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }

        // Filter Program Pagu
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }

        // Filter Kegiatan Pagu
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }

        // Filter Sub Kegiatan Pagu
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        // Filter Sumber Dana Pagu
        foreach (['kd_ref1', 'kd_ref2', 'kd_ref3', 'kd_ref4', 'kd_ref5', 'kd_ref6'] as $field) {
            if (!empty($sd[$field])) {
                $paguQuery->where(DB::raw("TRIM(rsd." . strtoupper($field) . ")"), trim($sd[$field]));
            }
        }

        // Filter Rekening Pagu
        if (!empty($request->rek_akun)) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING1)'), trim($request->rek_akun));
        }
        if (!empty($request->rek_kelompok['kd_kel1'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING1)'), trim($request->rek_kelompok['kd_kel1']));
        }
        if (!empty($request->rek_kelompok['kd_kel2'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING2)'), trim($request->rek_kelompok['kd_kel2']));
        }
        if (!empty($request->rek_jenis['kd_jenis1'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING1)'), trim($request->rek_jenis['kd_jenis1']));
        }
        if (!empty($request->rek_jenis['kd_jenis2'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING2)'), trim($request->rek_jenis['kd_jenis2']));
        }
        if (!empty($request->rek_jenis['kd_jenis3'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING3)'), trim($request->rek_jenis['kd_jenis3']));
        }
        if (!empty($request->rek_objek['kd_objek4'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING4)'), trim($request->rek_objek['kd_objek4']));
        }
        if (!empty($request->rek_rincian['kd_rincian5'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING5)'), trim($request->rek_rincian['kd_rincian5']));
        }
        if (!empty($request->sub_rincian['kd_subrincian6'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING6)'), trim($request->sub_rincian['kd_subrincian6']));
        }

        $paguRows = $paguQuery
            ->select([
                DB::raw("TRIM(rsd.KD_REF1)||'.'||TRIM(rsd.KD_REF2)||'.'||TRIM(rsd.KD_REF3)||'.'||TRIM(rsd.KD_REF4)||'.'||TRIM(rsd.KD_REF5)||'.'||TRIM(rsd.KD_REF6) as KODE_SUMBER_DANA"),
                'rsd.NM_REF as NM_SUMBER_DANA',
                DB::raw("TRIM(pb.KD_REKENING1)||'.'||TRIM(pb.KD_REKENING2)||'.'||TRIM(pb.KD_REKENING3) as KODE_BELANJA"),
                'rjb.NM_BELANJA as NM_BELANJA',
                DB::raw("TRIM(pb.KD_URUSAN) as KD_URUSAN"),
                'u.NM_URUSAN',
                DB::raw("TRIM(pb.KD_BU1)||'.'||TRIM(pb.KD_BU2) as KD_BU"),
                'bu.NM_BU',
                DB::raw("TRIM(pb.KD_PROG1)||'.'||TRIM(pb.KD_PROG2)||'.'||TRIM(pb.KD_PROG3) as KD_PROG"),
                'rp.NM_PROGRAM',
                DB::raw("TRIM(pb.KD_KEG1)||'.'||TRIM(pb.KD_KEG2)||'.'||TRIM(pb.KD_KEG3)||'.'||TRIM(pb.KD_KEG4)||'.'||TRIM(pb.KD_KEG5) as KD_KEG"),
                'rk.NM_KEGIATAN',
                DB::raw("TRIM(pb.KD_SUBKEG1)||'.'||TRIM(pb.KD_SUBKEG2)||'.'||TRIM(pb.KD_SUBKEG3)||'.'||TRIM(pb.KD_SUBKEG4)||'.'||TRIM(pb.KD_SUBKEG5)||'.'||TRIM(pb.KD_SUBKEG6) as KD_SUB"),
                'rs.NM_SUBKEGIATAN',
                DB::raw("SUM(pb.JUMLAH_PAGU) as ANGGARAN")
            ])
            ->groupBy(
                'rsd.KD_REF1', 'rsd.KD_REF2', 'rsd.KD_REF3', 'rsd.KD_REF4', 'rsd.KD_REF5', 'rsd.KD_REF6',
                'rsd.NM_REF',
                'pb.KD_REKENING1', 'pb.KD_REKENING2', 'pb.KD_REKENING3', 'rjb.NM_BELANJA',
                'pb.KD_URUSAN', 'u.NM_URUSAN',
                'pb.KD_BU1', 'pb.KD_BU2', 'bu.NM_BU',
                'pb.KD_PROG1', 'pb.KD_PROG2', 'pb.KD_PROG3', 'rp.NM_PROGRAM',
                'pb.KD_KEG1', 'pb.KD_KEG2', 'pb.KD_KEG3', 'pb.KD_KEG4', 'pb.KD_KEG5', 'rk.NM_KEGIATAN',
                'pb.KD_SUBKEG1', 'pb.KD_SUBKEG2', 'pb.KD_SUBKEG3', 'pb.KD_SUBKEG4', 'pb.KD_SUBKEG5', 'pb.KD_SUBKEG6', 'rs.NM_SUBKEGIATAN'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. GABUNGKAN ANGGARAN DAN REALISASI BERJENJANG
        |--------------------------------------------------------------------------
        */
        $maxLevel = $this->determineMaxLevel($request, 3);
        $data = $this->buildHierarchicalRowsForSumberDana($paguRows->all(), $realisasiRows->all(), $maxLevel);

        $pdf = Pdf::loadView(
            'laporan_pembukuan.lra_sumber_dana.cetak_pdf',
            [
                'tanggal' => $tglAkhir,
                'from'    => $tglAwal,
                'tahun'   => $tahun,
                'skpd'    => $request->skpd,
                'meta'    => $this->resolveFilterMeta($request),
                'data'    => $data
            ]
        );
        
        return $pdf
            ->setPaper('A4', 'landscape')
            ->stream('LRA_SUMBER_DANA.pdf');
    }
}