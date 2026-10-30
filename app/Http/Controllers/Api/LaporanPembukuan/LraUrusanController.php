<?php

namespace App\Http\Controllers\Api\LaporanPembukuan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\ResolvesFilterMeta;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\BuildsHierarchicalLra;

class LraUrusanController extends Controller
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
        | 1. QUERY REALISASI DARI SP2D_REKENING + SP2D
        |--------------------------------------------------------------------------
        */
        $realisasiQuery = DB::connection('oracle')
            ->table('SP2D_REKENING as r')
            ->join('SP2D as s', 's.ID_SP2D', '=', 'r.SP2D_ID')
            ->leftJoin('REF_URUSAN as u', DB::raw("TRIM(u.KD_URUSAN)"), '=', DB::raw("TRIM(r.KD_URUSAN)"))
            ->leftJoin('REF_BIDANG_URUSAN as bu', function ($j) {
                $j->on(DB::raw("TRIM(bu.KD_BU1)"), '=', DB::raw("TRIM(r.KD_BU1)"))
                  ->on(DB::raw("TRIM(bu.KD_BU2)"), '=', DB::raw("TRIM(r.KD_BU2)"));
            })
            ->leftJoin('REF_PROGRAM as p', function ($j) {
                $j->on(DB::raw("TRIM(p.KD_PROG1)"), '=', DB::raw("TRIM(r.KD_PROG1)"))
                  ->on(DB::raw("TRIM(p.KD_PROG2)"), '=', DB::raw("TRIM(r.KD_PROG2)"))
                  ->on(DB::raw("TRIM(p.KD_PROG3)"), '=', DB::raw("TRIM(r.KD_PROG3)"));
            })
            ->leftJoin('REF_KEGIATAN as k', function ($j) {
                $j->on(DB::raw("TRIM(k.KD_KEG1)"), '=', DB::raw("TRIM(r.KD_KEG1)"))
                  ->on(DB::raw("TRIM(k.KD_KEG2)"), '=', DB::raw("TRIM(r.KD_KEG2)"))
                  ->on(DB::raw("TRIM(k.KD_KEG3)"), '=', DB::raw("TRIM(r.KD_KEG3)"))
                  ->on(DB::raw("TRIM(k.KD_KEG4)"), '=', DB::raw("TRIM(r.KD_KEG4)"))
                  ->on(DB::raw("TRIM(k.KD_KEG5)"), '=', DB::raw("TRIM(r.KD_KEG5)"));
            })
            ->leftJoin('REF_SUBKEGIATAN as sub', function ($j) {
                $j->on(DB::raw("TRIM(sub.KD_SUBKEG1)"), '=', DB::raw("TRIM(r.KD_SUBKEG1)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG2)"), '=', DB::raw("TRIM(r.KD_SUBKEG2)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG3)"), '=', DB::raw("TRIM(r.KD_SUBKEG3)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG4)"), '=', DB::raw("TRIM(r.KD_SUBKEG4)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG5)"), '=', DB::raw("TRIM(r.KD_SUBKEG5)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG6)"), '=', DB::raw("TRIM(r.KD_SUBKEG6)"));
            })
            ->leftJoin('REF_JENIS_BELANJA as rjb', function ($j) {
                $j->on(DB::raw("TRIM(rjb.KD_REF1)"), '=', DB::raw("TRIM(r.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF2)"), '=', DB::raw("TRIM(r.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF3)"), '=', DB::raw("TRIM(r.KD_REKENING3)"));
            });

        // Filter Urusan
        $rawUrusan = $request->urusan;
        $filterUrusan = is_array($rawUrusan) ? trim($rawUrusan['kd_urusan'] ?? '') : trim((string)$rawUrusan);
        if (!empty($filterUrusan)) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_URUSAN)'), $filterUrusan);
        }

        // Filter Bidang Urusan
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }

        // Filter SKPD
        $opd = $request->skpd;
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(s." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }

        // Filter Program
        $program = $request->program;
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }

        // Filter Kegiatan
        $kegiatan = $request->kegiatan;
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }

        // Filter Sub Kegiatan
        $sub = $request->subkegiatan;
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $realisasiQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        // Filter Rekening Realisasi (Akun 5 = Belanja)
        $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), '5');

        if (!empty($request->rek_kelompok['kd_kel1'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), trim($request->rek_kelompok['kd_kel1']));
        }
        if (!empty($request->rek_kelompok['kd_kel2'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING2)'), trim($request->rek_kelompok['kd_kel2']));
        }
        if (!empty($request->rek_jenis['kd_jenis1'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), trim($request->rek_jenis['kd_jenis1']));
        }
        if (!empty($request->rek_jenis['kd_jenis2'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING2)'), trim($request->rek_jenis['kd_jenis2']));
        }
        if (!empty($request->rek_jenis['kd_jenis3'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING3)'), trim($request->rek_jenis['kd_jenis3']));
        }
        if (!empty($request->rek_objek['kd_objek4'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING4)'), trim($request->rek_objek['kd_objek4']));
        }
        if (!empty($request->rek_rincian['kd_rincian5'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING5)'), trim($request->rek_rincian['kd_rincian5']));
        }
        if (!empty($request->sub_rincian['kd_subrincian6'])) {
            $realisasiQuery->where(DB::raw('TRIM(r.KD_REKENING6)'), trim($request->sub_rincian['kd_subrincian6']));
        }

        // Filter Tanggal Realisasi
        $realisasiQuery->whereBetween(
            DB::raw('TRUNC(s.TANGGAL_UPLOAD)'),
            [
                DB::raw("TO_DATE('{$startOfYear}','YYYY-MM-DD')"),
                DB::raw("TO_DATE('{$tglAkhir}','YYYY-MM-DD')")
            ]
        );

        $realisasiRows = $realisasiQuery
            ->select([
                DB::raw("TRIM(r.KD_REKENING1)||'.'||TRIM(r.KD_REKENING2)||'.'||TRIM(r.KD_REKENING3) as KODE_BELANJA"),
                DB::raw("TRIM(rjb.NM_BELANJA) as NM_BELANJA"),
                DB::raw("TRIM(r.KD_URUSAN) as KD_URUSAN"),
                DB::raw("TRIM(u.NM_URUSAN) as NM_URUSAN"),
                DB::raw("TRIM(r.KD_BU1)||'.'||TRIM(r.KD_BU2) as KD_BU"),
                DB::raw("TRIM(bu.NM_BU) as NM_BU"),
                DB::raw("TRIM(r.KD_PROG1)||'.'||TRIM(r.KD_PROG2)||'.'||TRIM(r.KD_PROG3) as KD_PROG"),
                DB::raw("TRIM(p.NM_PROGRAM) as NM_PROGRAM"),
                DB::raw("TRIM(r.KD_KEG1)||'.'||TRIM(r.KD_KEG2)||'.'||TRIM(r.KD_KEG3)||'.'||TRIM(r.KD_KEG4)||'.'||TRIM(r.KD_KEG5) as KD_KEG"),
                DB::raw("TRIM(k.NM_KEGIATAN) as NM_KEGIATAN"),
                DB::raw("TRIM(r.KD_SUBKEG1)||'.'||TRIM(r.KD_SUBKEG2)||'.'||TRIM(r.KD_SUBKEG3)||'.'||TRIM(r.KD_SUBKEG4)||'.'||TRIM(r.KD_SUBKEG5)||'.'||TRIM(r.KD_SUBKEG6) as KD_SUB"),
                DB::raw("TRIM(sub.NM_SUBKEGIATAN) as NM_SUBKEGIATAN"),
                DB::raw("SUM(CASE WHEN TRUNC(s.TANGGAL_UPLOAD) < TO_DATE('{$tglAwal}', 'YYYY-MM-DD') THEN r.NILAI ELSE 0 END) AS REALISASI_LALU"),
                DB::raw("SUM(CASE WHEN TRUNC(s.TANGGAL_UPLOAD) >= TO_DATE('{$tglAwal}', 'YYYY-MM-DD') AND TRUNC(s.TANGGAL_UPLOAD) <= TO_DATE('{$tglAkhir}', 'YYYY-MM-DD') THEN r.NILAI ELSE 0 END) AS REALISASI_INI"),
                DB::raw("SUM(r.NILAI) AS REALISASI_TOTAL")
            ])
            ->groupBy(
                'r.KD_REKENING1', 'r.KD_REKENING2', 'r.KD_REKENING3', 'rjb.NM_BELANJA',
                'r.KD_URUSAN', 'u.NM_URUSAN',
                'r.KD_BU1', 'r.KD_BU2', 'bu.NM_BU',
                'r.KD_PROG1', 'r.KD_PROG2', 'r.KD_PROG3', 'p.NM_PROGRAM',
                'r.KD_KEG1', 'r.KD_KEG2', 'r.KD_KEG3', 'r.KD_KEG4', 'r.KD_KEG5', 'k.NM_KEGIATAN',
                'r.KD_SUBKEG1', 'r.KD_SUBKEG2', 'r.KD_SUBKEG3', 'r.KD_SUBKEG4', 'r.KD_SUBKEG5', 'r.KD_SUBKEG6', 'sub.NM_SUBKEGIATAN'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. QUERY ANGGARAN DARI PAGU_BELANJA
        |--------------------------------------------------------------------------
        */
        $paguQuery = DB::connection('oracle')
            ->table('PAGU_BELANJA as pb')
            ->leftJoin('REF_URUSAN as u', DB::raw("TRIM(u.KD_URUSAN)"), '=', DB::raw("TRIM(pb.KD_URUSAN)"))
            ->leftJoin('REF_BIDANG_URUSAN as bu', function ($j) {
                $j->on(DB::raw("TRIM(bu.KD_BU1)"), '=', DB::raw("TRIM(pb.KD_BU1)"))
                  ->on(DB::raw("TRIM(bu.KD_BU2)"), '=', DB::raw("TRIM(pb.KD_BU2)"));
            })
            ->leftJoin('REF_PROGRAM as p', function ($j) {
                $j->on(DB::raw("TRIM(p.KD_PROG1)"), '=', DB::raw("TRIM(pb.KD_PROG1)"))
                  ->on(DB::raw("TRIM(p.KD_PROG2)"), '=', DB::raw("TRIM(pb.KD_PROG2)"))
                  ->on(DB::raw("TRIM(p.KD_PROG3)"), '=', DB::raw("TRIM(pb.KD_PROG3)"));
            })
            ->leftJoin('REF_KEGIATAN as k', function ($j) {
                $j->on(DB::raw("TRIM(k.KD_KEG1)"), '=', DB::raw("TRIM(pb.KD_KEG1)"))
                  ->on(DB::raw("TRIM(k.KD_KEG2)"), '=', DB::raw("TRIM(pb.KD_KEG2)"))
                  ->on(DB::raw("TRIM(k.KD_KEG3)"), '=', DB::raw("TRIM(pb.KD_KEG3)"))
                  ->on(DB::raw("TRIM(k.KD_KEG4)"), '=', DB::raw("TRIM(pb.KD_KEG4)"))
                  ->on(DB::raw("TRIM(k.KD_KEG5)"), '=', DB::raw("TRIM(pb.KD_KEG5)"));
            })
            ->leftJoin('REF_SUBKEGIATAN as sub', function ($j) {
                $j->on(DB::raw("TRIM(sub.KD_SUBKEG1)"), '=', DB::raw("TRIM(pb.KD_SUBKEG1)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG2)"), '=', DB::raw("TRIM(pb.KD_SUBKEG2)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG3)"), '=', DB::raw("TRIM(pb.KD_SUBKEG3)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG4)"), '=', DB::raw("TRIM(pb.KD_SUBKEG4)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG5)"), '=', DB::raw("TRIM(pb.KD_SUBKEG5)"))
                  ->on(DB::raw("TRIM(sub.KD_SUBKEG6)"), '=', DB::raw("TRIM(pb.KD_SUBKEG6)"));
            })
            ->leftJoin('REF_JENIS_BELANJA as rjb', function ($j) {
                $j->on(DB::raw("TRIM(rjb.KD_REF1)"), '=', DB::raw("TRIM(pb.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF2)"), '=', DB::raw("TRIM(pb.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rjb.KD_REF3)"), '=', DB::raw("TRIM(pb.KD_REKENING3)"));
            })
            ->where('pb.IS_DELETED', 0)
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

        // Filter Rekening Pagu (Akun 5 = Belanja)
        $paguQuery->where(DB::raw('TRIM(pb.KD_REKENING1)'), '5');

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
                DB::raw("TRIM(pb.KD_REKENING1)||'.'||TRIM(pb.KD_REKENING2)||'.'||TRIM(pb.KD_REKENING3) as KODE_BELANJA"),
                DB::raw("TRIM(rjb.NM_BELANJA) as NM_BELANJA"),
                DB::raw("TRIM(pb.KD_URUSAN) as KD_URUSAN"),
                DB::raw("TRIM(u.NM_URUSAN) as NM_URUSAN"),
                DB::raw("TRIM(pb.KD_BU1)||'.'||TRIM(pb.KD_BU2) as KD_BU"),
                DB::raw("TRIM(bu.NM_BU) as NM_BU"),
                DB::raw("TRIM(pb.KD_PROG1)||'.'||TRIM(pb.KD_PROG2)||'.'||TRIM(pb.KD_PROG3) as KD_PROG"),
                DB::raw("TRIM(p.NM_PROGRAM) as NM_PROGRAM"),
                DB::raw("TRIM(pb.KD_KEG1)||'.'||TRIM(pb.KD_KEG2)||'.'||TRIM(pb.KD_KEG3)||'.'||TRIM(pb.KD_KEG4)||'.'||TRIM(pb.KD_KEG5) as KD_KEG"),
                DB::raw("TRIM(k.NM_KEGIATAN) as NM_KEGIATAN"),
                DB::raw("TRIM(pb.KD_SUBKEG1)||'.'||TRIM(pb.KD_SUBKEG2)||'.'||TRIM(pb.KD_SUBKEG3)||'.'||TRIM(pb.KD_SUBKEG4)||'.'||TRIM(pb.KD_SUBKEG5)||'.'||TRIM(pb.KD_SUBKEG6) as KD_SUB"),
                DB::raw("TRIM(sub.NM_SUBKEGIATAN) as NM_SUBKEGIATAN"),
                DB::raw("SUM(pb.JUMLAH_PAGU) as ANGGARAN")
            ])
            ->groupBy(
                'pb.KD_REKENING1', 'pb.KD_REKENING2', 'pb.KD_REKENING3', 'rjb.NM_BELANJA',
                'pb.KD_URUSAN', 'u.NM_URUSAN',
                'pb.KD_BU1', 'pb.KD_BU2', 'bu.NM_BU',
                'pb.KD_PROG1', 'pb.KD_PROG2', 'pb.KD_PROG3', 'p.NM_PROGRAM',
                'pb.KD_KEG1', 'pb.KD_KEG2', 'pb.KD_KEG3', 'pb.KD_KEG4', 'pb.KD_KEG5', 'k.NM_KEGIATAN',
                'pb.KD_SUBKEG1', 'pb.KD_SUBKEG2', 'pb.KD_SUBKEG3', 'pb.KD_SUBKEG4', 'pb.KD_SUBKEG5', 'pb.KD_SUBKEG6', 'sub.NM_SUBKEGIATAN'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. GABUNGKAN ANGGARAN DAN REALISASI BERJENJANG
        |--------------------------------------------------------------------------
        */
        $maxLevel = $this->determineMaxLevel($request, 3);
        $data = $this->buildHierarchicalRows($paguRows->all(), $realisasiRows->all(), $maxLevel);

        $pdf = Pdf::loadView(
            'laporan_pembukuan.lra_per_urusan.cetak_pdf',
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
            ->stream('LRA_PER_URUSAN.pdf');
    }
}