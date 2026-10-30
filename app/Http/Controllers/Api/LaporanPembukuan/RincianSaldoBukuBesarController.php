<?php

namespace App\Http\Controllers\Api\LaporanPembukuan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Api\LaporanPembukuan\Traits\ResolvesFilterMeta;

class RincianSaldoBukuBesarController extends Controller
{
    use ResolvesFilterMeta;
    public function index(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

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
        | 1. QUERY ANGGARAN (PAGU) PER REKENING
        |--------------------------------------------------------------------------
        */
        $paguQuery = DB::connection('oracle')
            ->table('PAGU_BELANJA as pb')
            ->leftJoin('REF_REKENING as rr', function ($j) {
                $j->on(DB::raw("TRIM(rr.KD_REKENING1)"), '=', DB::raw("TRIM(pb.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING2)"), '=', DB::raw("TRIM(pb.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING3)"), '=', DB::raw("TRIM(pb.KD_REKENING3)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING4)"), '=', DB::raw("TRIM(pb.KD_REKENING4)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING5)"), '=', DB::raw("TRIM(pb.KD_REKENING5)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING6)"), '=', DB::raw("TRIM(pb.KD_REKENING6)"));
            })
            ->where('pb.IS_DELETED', 0)
            ->where(DB::raw("TRIM(pb.TAHUN_REK)"), $tahun);

        // Filter SKPD
        $opd = $request->skpd;
        foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $field) {
            if (!empty($opd[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }

        // Filter Urusan & Bidang Urusan
        $rawUrusan = $request->urusan;
        $filterUrusan = is_array($rawUrusan) ? trim($rawUrusan['kd_urusan'] ?? '') : trim((string)$rawUrusan);
        if (!empty($filterUrusan)) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_URUSAN)'), $filterUrusan);
        }
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $paguQuery->where(DB::raw('TRIM(pb.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }

        // Filter Program
        $program = $request->program;
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }

        // Filter Kegiatan
        $kegiatan = $request->kegiatan;
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }

        // Filter Sub Kegiatan
        $sub = $request->subkegiatan;
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $paguQuery->where(DB::raw("TRIM(pb." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }

        // Filter Rekening
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
                DB::raw("TRIM(pb.KD_REKENING1)||'.'||TRIM(pb.KD_REKENING2)||'.'||TRIM(pb.KD_REKENING3)||'.'||TRIM(pb.KD_REKENING4)||'.'||TRIM(pb.KD_REKENING5)||'.'||TRIM(pb.KD_REKENING6) as KODE_REKENING"),
                DB::raw("TRIM(rr.NM_REKENING) as NM_REKENING"),
                DB::raw("SUM(pb.JUMLAH_PAGU) as ANGGARAN")
            ])
            ->groupBy(
                'pb.KD_REKENING1', 'pb.KD_REKENING2', 'pb.KD_REKENING3', 'pb.KD_REKENING4', 'pb.KD_REKENING5', 'pb.KD_REKENING6',
                'rr.NM_REKENING'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. QUERY TRANSAKSI SP2D_REKENING + SP2D
        |--------------------------------------------------------------------------
        */
        $trxQuery = DB::connection('oracle')
            ->table('SP2D_REKENING as r')
            ->join('SP2D as s', 's.ID_SP2D', '=', 'r.SP2D_ID')
            ->leftJoin('REF_REKENING as rr', function ($j) {
                $j->on(DB::raw("TRIM(rr.KD_REKENING1)"), '=', DB::raw("TRIM(r.KD_REKENING1)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING2)"), '=', DB::raw("TRIM(r.KD_REKENING2)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING3)"), '=', DB::raw("TRIM(r.KD_REKENING3)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING4)"), '=', DB::raw("TRIM(r.KD_REKENING4)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING5)"), '=', DB::raw("TRIM(r.KD_REKENING5)"))
                  ->on(DB::raw("TRIM(rr.KD_REKENING6)"), '=', DB::raw("TRIM(r.KD_REKENING6)"));
            })
            ->leftJoin('REF_OPD as opd', function ($j) {
                $j->on(DB::raw("TRIM(opd.KD_OPD1)"), '=', DB::raw("TRIM(s.KD_OPD1)"))
                  ->on(DB::raw("TRIM(opd.KD_OPD2)"), '=', DB::raw("TRIM(s.KD_OPD2)"))
                  ->on(DB::raw("TRIM(opd.KD_OPD3)"), '=', DB::raw("TRIM(s.KD_OPD3)"))
                  ->on(DB::raw("TRIM(opd.KD_OPD4)"), '=', DB::raw("TRIM(s.KD_OPD4)"))
                  ->on(DB::raw("TRIM(opd.KD_OPD5)"), '=', DB::raw("TRIM(s.KD_OPD5)"));
            })
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
                $trxQuery->where(DB::raw("TRIM(s." . strtoupper($field) . ")"), trim($opd[$field]));
            }
        }
        if (!empty($filterUrusan)) {
            $trxQuery->where(DB::raw('TRIM(r.KD_URUSAN)'), $filterUrusan);
        }
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_BU1)'), trim($request->bidang_urusan['kd_bu1']));
        }
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_BU2)'), trim($request->bidang_urusan['kd_bu2']));
        }
        foreach (['kd_prog1', 'kd_prog2', 'kd_prog3'] as $field) {
            if (!empty($program[$field])) {
                $trxQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($program[$field]));
            }
        }
        foreach (['kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5'] as $field) {
            if (!empty($kegiatan[$field])) {
                $trxQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($kegiatan[$field]));
            }
        }
        foreach (['kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6'] as $field) {
            if (!empty($sub[$field])) {
                $trxQuery->where(DB::raw("TRIM(r." . strtoupper($field) . ")"), trim($sub[$field]));
            }
        }
        if (!empty($request->rek_akun)) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), trim($request->rek_akun));
        }
        if (!empty($request->rek_kelompok['kd_kel1'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), trim($request->rek_kelompok['kd_kel1']));
        }
        if (!empty($request->rek_kelompok['kd_kel2'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING2)'), trim($request->rek_kelompok['kd_kel2']));
        }
        if (!empty($request->rek_jenis['kd_jenis1'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING1)'), trim($request->rek_jenis['kd_jenis1']));
        }
        if (!empty($request->rek_jenis['kd_jenis2'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING2)'), trim($request->rek_jenis['kd_jenis2']));
        }
        if (!empty($request->rek_jenis['kd_jenis3'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING3)'), trim($request->rek_jenis['kd_jenis3']));
        }
        if (!empty($request->rek_objek['kd_objek4'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING4)'), trim($request->rek_objek['kd_objek4']));
        }
        if (!empty($request->rek_rincian['kd_rincian5'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING5)'), trim($request->rek_rincian['kd_rincian5']));
        }
        if (!empty($request->sub_rincian['kd_subrincian6'])) {
            $trxQuery->where(DB::raw('TRIM(r.KD_REKENING6)'), trim($request->sub_rincian['kd_subrincian6']));
        }

        $trxRows = $trxQuery
            ->select([
                DB::raw("TRUNC(s.TANGGAL_UPLOAD) as TANGGAL"),
                's.NO_SPM',
                's.JENIS_BERKAS',
                'opd.NM_OPD',
                DB::raw("TRIM(r.KD_REKENING1)||'.'||TRIM(r.KD_REKENING2)||'.'||TRIM(r.KD_REKENING3)||'.'||TRIM(r.KD_REKENING4)||'.'||TRIM(r.KD_REKENING5)||'.'||TRIM(r.KD_REKENING6) as KODE_REKENING"),
                DB::raw("TRIM(rr.NM_REKENING) as NM_REKENING"),
                'r.NILAI'
            ])
            ->orderBy('s.TANGGAL_UPLOAD', 'asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. GABUNGKAN DATA BUKU BESAR PER REKENING
        |--------------------------------------------------------------------------
        */
        $rekeningMap = [];

        // Masukkan Pagu
        foreach ($paguRows as $p) {
            $kRek = trim($p->kode_rekening ?? $p->KODE_REKENING ?? '');
            if (!$kRek || $kRek === '.....') continue;
            $nRek = trim($p->nm_rekening ?? $p->NM_REKENING ?? "REKENING $kRek");

            if (!isset($rekeningMap[$kRek])) {
                $rekeningMap[$kRek] = [
                    'kode' => $kRek,
                    'nama' => $nRek,
                    'anggaran' => 0,
                    'realisasi_lalu' => 0,
                    'realisasi_ini' => 0,
                    'transaksi' => []
                ];
            }
            $rekeningMap[$kRek]['anggaran'] += (float) ($p->anggaran ?? $p->ANGGARAN ?? 0);
            if (empty($rekeningMap[$kRek]['nama']) && !empty($nRek)) {
                $rekeningMap[$kRek]['nama'] = $nRek;
            }
        }

        // Masukkan Transaksi Realisasi
        foreach ($trxRows as $t) {
            $kRek = trim($t->kode_rekening ?? $t->KODE_REKENING ?? '');
            if (!$kRek || $kRek === '.....') continue;
            $nRek = trim($t->nm_rekening ?? $t->NM_REKENING ?? "REKENING $kRek");
            $nilai = (float) ($t->nilai ?? $t->NILAI ?? 0);
            $tgl = Carbon::parse($t->tanggal ?? $t->TANGGAL)->format('Y-m-d');

            if (!isset($rekeningMap[$kRek])) {
                $rekeningMap[$kRek] = [
                    'kode' => $kRek,
                    'nama' => $nRek,
                    'anggaran' => 0,
                    'realisasi_lalu' => 0,
                    'realisasi_ini' => 0,
                    'transaksi' => []
                ];
            }
            if (empty($rekeningMap[$kRek]['nama']) && !empty($nRek)) {
                $rekeningMap[$kRek]['nama'] = $nRek;
            }

            if ($tgl < $tglAwal) {
                $rekeningMap[$kRek]['realisasi_lalu'] += $nilai;
            } else {
                $rekeningMap[$kRek]['realisasi_ini'] += $nilai;
                $rekeningMap[$kRek]['transaksi'][] = [
                    'tanggal' => $tgl,
                    'no_spm' => $t->no_spm ?? $t->NO_SPM ?? '-',
                    'jenis' => $t->jenis_berkas ?? $t->JENIS_BERKAS ?? 'SP2D',
                    'uraian' => $t->nm_opd ?? $t->NM_OPD ?? 'Pembayaran Belanja',
                    'nilai' => $nilai
                ];
            }
        }

        // Hitung saldo berjalan kumulatif per rekening & filter hanya yang terpakai
        $data = collect($rekeningMap)->filter(function ($r) {
            return ((float)$r['anggaran'] != 0)
                || ((float)$r['realisasi_lalu'] != 0)
                || ((float)$r['realisasi_ini'] != 0);
        })->map(function ($r) {
            $runningRealisasi = (float)$r['realisasi_lalu'];
            $trxList = [];
            foreach ($r['transaksi'] as $tx) {
                $runningRealisasi += (float)$tx['nilai'];
                $sisa = (float)$r['anggaran'] - $runningRealisasi;
                $tx['kumulatif'] = $runningRealisasi;
                $tx['sisa'] = $sisa;
                $trxList[] = $tx;
            }

            $totalRealisasi = (float)$r['realisasi_lalu'] + (float)$r['realisasi_ini'];
            $sisaAkhir = (float)$r['anggaran'] - $totalRealisasi;
            $persen = (float)$r['anggaran'] > 0 ? ($totalRealisasi / (float)$r['anggaran']) * 100 : 0;

            return [
                'kode' => $r['kode'],
                'nama' => $r['nama'],
                'anggaran' => (float)$r['anggaran'],
                'realisasi_lalu' => (float)$r['realisasi_lalu'],
                'realisasi_ini' => (float)$r['realisasi_ini'],
                'total_realisasi' => $totalRealisasi,
                'sisa' => $sisaAkhir,
                'persen' => $persen,
                'transaksi' => $trxList
            ];
        })->sortBy('kode')->values();

        $pdf = Pdf::loadView(
            'laporan_pembukuan.rincian_saldo_buku_besar.cetak_pdf',
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
            ->stream('RINCIAN_SALDO_BUKU_BESAR.pdf');
    }
}