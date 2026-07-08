<?php

namespace App\Http\Controllers\Api\LaporanPembukuan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class LraSumberDanaController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = Carbon::parse($request->tanggal);

        $tahun = $tanggal->year;
        $tglAwal = Carbon::parse($request->range_tanggal['from'])->format('Y-m-d');
        $tglAkhir = $tanggal->format('Y-m-d');

        $query = DB::connection('oracle')
        ->table('VIEW_LRA_SUMBER_DANA');

        /*
        |--------------------------------------------------------------------------
        | FILTER URUSAN
        |--------------------------------------------------------------------------
        */

        if (!empty(trim($request->urusan))) {
            $query->where('KD_URUSAN', trim($request->urusan));
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER BIDANG URUSAN
        |--------------------------------------------------------------------------
        */

        if (!empty($request->bidang_urusan['kd_bu1'])) {
            $query->where('KD_BU1', $request->bidang_urusan['kd_bu1']);
        }
        
        if (!empty($request->bidang_urusan['kd_bu2'])) {
            $query->where('KD_BU2', $request->bidang_urusan['kd_bu2']);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER OPD
        |--------------------------------------------------------------------------
        */

        $opd = $request->skpd;

        foreach ([
            'kd_opd1',
            'kd_opd2',
            'kd_opd3',
            'kd_opd4',
            'kd_opd5'
        ] as $field) {
        
            if (!empty($opd[$field])) {
                $query->where(strtoupper($field), $opd[$field]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PROGRAM
        |--------------------------------------------------------------------------
        */

        $program = $request->program;

        foreach ([
            'kd_prog1',
            'kd_prog2',
            'kd_prog3'
        ] as $field) {
        
            if (!empty($program[$field])) {
                $query->where(strtoupper($field), $program[$field]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER KEGIATAN
        |--------------------------------------------------------------------------
        */

        $kegiatan = $request->kegiatan;

        foreach ([
            'kd_keg1',
            'kd_keg2',
            'kd_keg3',
            'kd_keg4',
            'kd_keg5'
        ] as $field) {
        
            if (!empty($kegiatan[$field])) {
                $query->where(strtoupper($field), $kegiatan[$field]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER SUB KEGIATAN
        |--------------------------------------------------------------------------
        */

        $sub = $request->subkegiatan;

        foreach ([
            'kd_subkeg1',
            'kd_subkeg2',
            'kd_subkeg3',
            'kd_subkeg4',
            'kd_subkeg5',
            'kd_subkeg6'
        ] as $field) {
        
            if (!empty($sub[$field])) {
                $query->where(strtoupper($field), $sub[$field]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER SUMBER DANA
        |--------------------------------------------------------------------------
        */

        $sd = $request->sumber_dana;

        foreach ([
            'kd_ref1',
            'kd_ref2',
            'kd_ref3',
            'kd_ref4',
            'kd_ref5',
            'kd_ref6'
        ] as $field) {
        
            if (!empty($sd[$field])) {
                $query->where(strtoupper($field), $sd[$field]);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | FILTER REKENING
        |--------------------------------------------------------------------------
        */

        if (!empty($request->rek_akun)) {
            $query->where('KD_AKUN', $request->rek_akun);
        }

        if (!empty($request->rek_kelompok['kd_kel1'])) {
            $query->where('KD_KEL1', $request->rek_kelompok['kd_kel1']);
        }

        if (!empty($request->rek_kelompok['kd_kel2'])) {
            $query->where('KD_KEL2', $request->rek_kelompok['kd_kel2']);
        }

        if (!empty($request->rek_jenis['kd_jenis1'])) {
            $query->where('KD_JENIS1', $request->rek_jenis['kd_jenis1']);
        }

        if (!empty($request->rek_jenis['kd_jenis2'])) {
            $query->where('KD_JENIS2', $request->rek_jenis['kd_jenis2']);
        }

        if (!empty($request->rek_jenis['kd_jenis3'])) {
            $query->where('KD_JENIS3', $request->rek_jenis['kd_jenis3']);
        }

        if (!empty($request->rek_objek['kd_objek4'])) {
            $query->where('KD_OBJEK4', $request->rek_objek['kd_objek4']);
        }

        if (!empty($request->rek_rincian['kd_rincian5'])) {
            $query->where('KD_RINCIAN5', $request->rek_rincian['kd_rincian5']);
        }

        if (!empty($request->sub_rincian['kd_subrincian6'])) {
            $query->where('KD_SUBRINCIAN6', $request->sub_rincian['kd_subrincian6']);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */

        $query->whereBetween(
            DB::raw('TRUNC(TANGGAL)'),
            [
                DB::raw("TO_DATE('{$tglAwal}','YYYY-MM-DD')"),
                DB::raw("TO_DATE('{$tglAkhir}','YYYY-MM-DD')")
            ]
        );

        $rows = $query
        ->orderBy('KODE_SUMBER_DANA')
        ->orderBy('KD_BELANJA1')
        ->orderBy('KD_BELANJA2')
        ->orderBy('KD_BELANJA3')
        ->orderBy('KODE_REKENING')
        ->get();


        $data = $rows
        ->groupBy('kode_sumber_dana')
        ->map(function ($items) {
    
            return [
    
                'kode' => $items->first()->kode_sumber_dana,
    
                'nama' => $items->first()->nm_sumber_dana,
    
                'total' => $items->sum('nilai'),
    
                'belanja' => $items
                    ->groupBy('kode_belanja')
                    ->map(function ($belanja) {
    
                        return [
    
                            'kode' => $belanja->first()->kode_belanja,
    
                            'nama' => $belanja->first()->nm_belanja,
    
                            'total' => $belanja->sum('nilai'),
    
                        ];
    
                    })->values()
    
            ];
    
        })->values();

    $pdf = Pdf::loadView(
        'laporan_pembukuan.lra_sumber_dana.cetak_pdf',
        [
            'tanggal' => $request->tanggal,
            'from'    => $request->range_tanggal['from'],
            'data'    => $data
        ]
    );
    
    return $pdf
        ->setPaper('A4', 'landscape')
        ->stream('LRA_SUMBER_DANA.pdf');
    }
}