<?php

namespace App\Http\Controllers\Api\LaporanPembukuan\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ResolvesFilterMeta
{
    public function resolveFilterMeta(Request $request): array
    {
        $meta = [];

        // 1. Urusan
        $rawUrusan = $request->urusan;
        $kdUrusan = is_array($rawUrusan) ? trim($rawUrusan['kd_urusan'] ?? '') : trim((string)$rawUrusan);
        if (!empty($kdUrusan)) {
            $nmUrusan = trim($request->input('nm_urusan') ?? '');
            if (!$nmUrusan) {
                $uRow = DB::connection('oracle')->table('REF_URUSAN')
                    ->where(DB::raw('TRIM(KD_URUSAN)'), $kdUrusan)
                    ->first();
                $nmUrusan = trim($uRow->nm_urusan ?? $uRow->NM_URUSAN ?? '');
            }
            $meta['urusan'] = "$kdUrusan - $nmUrusan";
        }

        // 2. Bidang Urusan
        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $kdBu1 = trim($request->bidang_urusan['kd_bu1']);
            $kdBu2 = trim($request->bidang_urusan['kd_bu2'] ?? '');
            $nmBu = trim($request->bidang_urusan['nm_bu'] ?? '');
            if (!$nmBu) {
                $buRow = DB::connection('oracle')->table('REF_BIDANG_URUSAN')
                    ->where(DB::raw('TRIM(KD_BU1)'), $kdBu1)
                    ->where(DB::raw('TRIM(KD_BU2)'), $kdBu2)
                    ->first();
                $nmBu = trim($buRow->nm_bu ?? $buRow->NM_BU ?? '');
            }
            $meta['bidang_urusan'] = "$kdBu1.$kdBu2 - $nmBu";
        }

        // 3. SKPD (OPD)
        $opd = $request->skpd;
        if (!empty($opd['kd_opd1'])) {
            $opd1 = trim($opd['kd_opd1']);
            $opd2 = trim($opd['kd_opd2'] ?? '');
            $opd3 = trim($opd['kd_opd3'] ?? '');
            $opd4 = trim($opd['kd_opd4'] ?? '');
            $opd5 = trim($opd['kd_opd5'] ?? '');
            $kdOpd = implode('.', array_filter([$opd1, $opd2, $opd3, $opd4, $opd5]));
            $nmOpd = trim($opd['nm_opd'] ?? '');
            if (!$nmOpd) {
                $opdRow = DB::connection('oracle')->table('REF_OPD')
                    ->where(DB::raw('TRIM(KD_OPD1)'), $opd1)
                    ->where(DB::raw('TRIM(KD_OPD2)'), $opd2)
                    ->where(DB::raw('TRIM(KD_OPD3)'), $opd3)
                    ->where(DB::raw('TRIM(KD_OPD4)'), $opd4)
                    ->where(DB::raw('TRIM(KD_OPD5)'), $opd5)
                    ->first();
                $nmOpd = trim($opdRow->nm_opd ?? $opdRow->NM_OPD ?? '');
            }
            $meta['skpd'] = "$kdOpd - $nmOpd";
        }

        // 4. Program
        $prog = $request->program;
        if (!empty($prog['kd_prog1'])) {
            $p1 = trim($prog['kd_prog1']);
            $p2 = trim($prog['kd_prog2'] ?? '');
            $p3 = trim($prog['kd_prog3'] ?? '');
            $kdProg = "$p1.$p2.$p3";
            $nmProg = trim($prog['nm_program'] ?? '');
            if (!$nmProg) {
                $pRow = DB::connection('oracle')->table('REF_PROGRAM')
                    ->where(DB::raw('TRIM(KD_PROG1)'), $p1)
                    ->where(DB::raw('TRIM(KD_PROG2)'), $p2)
                    ->where(DB::raw('TRIM(KD_PROG3)'), $p3)
                    ->first();
                $nmProg = trim($pRow->nm_program ?? $pRow->NM_PROGRAM ?? '');
            }
            $meta['program'] = "$kdProg - $nmProg";
        }

        // 5. Kegiatan
        $keg = $request->kegiatan;
        if (!empty($keg['kd_keg1'])) {
            $k1 = trim($keg['kd_keg1']);
            $k2 = trim($keg['kd_keg2'] ?? '');
            $k3 = trim($keg['kd_keg3'] ?? '');
            $k4 = trim($keg['kd_keg4'] ?? '');
            $k5 = trim($keg['kd_keg5'] ?? '');
            $kdKeg = "$k1.$k2.$k3.$k4.$k5";
            $nmKeg = trim($keg['nm_kegiatan'] ?? '');
            if (!$nmKeg) {
                $kRow = DB::connection('oracle')->table('REF_KEGIATAN')
                    ->where(DB::raw('TRIM(KD_KEG1)'), $k1)
                    ->where(DB::raw('TRIM(KD_KEG2)'), $k2)
                    ->where(DB::raw('TRIM(KD_KEG3)'), $k3)
                    ->where(DB::raw('TRIM(KD_KEG4)'), $k4)
                    ->where(DB::raw('TRIM(KD_KEG5)'), $k5)
                    ->first();
                $nmKeg = trim($kRow->nm_kegiatan ?? $kRow->NM_KEGIATAN ?? '');
            }
            $meta['kegiatan'] = "$kdKeg - $nmKeg";
        }

        // 6. Sub Kegiatan
        $sub = $request->subkegiatan;
        if (!empty($sub['kd_subkeg1'])) {
            $s1 = trim($sub['kd_subkeg1']);
            $s2 = trim($sub['kd_subkeg2'] ?? '');
            $s3 = trim($sub['kd_subkeg3'] ?? '');
            $s4 = trim($sub['kd_subkeg4'] ?? '');
            $s5 = trim($sub['kd_subkeg5'] ?? '');
            $s6 = trim($sub['kd_subkeg6'] ?? '');
            $kdSub = "$s1.$s2.$s3.$s4.$s5.$s6";
            $nmSub = trim($sub['nm_subkegiatan'] ?? '');
            if (!$nmSub) {
                $sRow = DB::connection('oracle')->table('REF_SUBKEGIATAN')
                    ->where(DB::raw('TRIM(KD_SUBKEG1)'), $s1)
                    ->where(DB::raw('TRIM(KD_SUBKEG2)'), $s2)
                    ->where(DB::raw('TRIM(KD_SUBKEG3)'), $s3)
                    ->where(DB::raw('TRIM(KD_SUBKEG4)'), $s4)
                    ->where(DB::raw('TRIM(KD_SUBKEG5)'), $s5)
                    ->where(DB::raw('TRIM(KD_SUBKEG6)'), $s6)
                    ->first();
                $nmSub = trim($sRow->nm_subkegiatan ?? $sRow->NM_SUBKEGIATAN ?? '');
            }
            $meta['subkegiatan'] = "$kdSub - $nmSub";
        }

        return $meta;
    }
}
