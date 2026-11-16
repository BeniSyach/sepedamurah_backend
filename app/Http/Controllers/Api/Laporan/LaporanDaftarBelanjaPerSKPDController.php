<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Exports\LaporanDaftarBelanjaSKPDExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanDaftarBelanjaPerSKPDController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));

        $data = DB::connection('oracle')->select("
            SELECT 
                a.kd_opd1,
                a.kd_opd2,
                a.kd_opd3,
                a.kd_opd4,
                a.kd_opd5,
                a.nm_opd,
                NVL(SUM(b.nilai_belanja), 0) AS jum_belanja
            FROM ref_opd a
            LEFT OUTER JOIN sp2d b
                ON a.kd_opd1 = b.kd_opd1
                AND a.kd_opd2 = b.kd_opd2
                AND a.kd_opd3 = b.kd_opd3
                AND a.kd_opd4 = b.kd_opd4
                AND a.kd_opd5 = b.kd_opd5
                AND b.tahun = :tahun
                AND b.diterima IS NOT NULL
            WHERE a.hidden = '0'
            GROUP BY 
                a.kd_opd1, a.kd_opd2, a.kd_opd3, a.kd_opd4, a.kd_opd5, a.nm_opd
            ORDER BY 
                a.kd_opd1, a.kd_opd2, a.kd_opd3, a.kd_opd4, a.kd_opd5
        ", ['tahun' => $tahun]);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function detail_daftar_belanja_SKPD(Request $request)
    {
        $params = $request->only([
            'kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5', 'tahun'
        ]);
    
        // Validasi parameter wajib
        foreach ($params as $key => $val) {
            if (!$val) {
                return response()->json([
                    'status'  => false,
                    'message' => "Parameter {$key} wajib diisi",
                ], 400);
            }
        }
    
        $sql = "
            SELECT 
                TRUNC(d.diterima) AS tanggal,
    
                COALESCE(
                    b.NM_REF,
                    (
                        SELECT LISTAGG(D.NM_REF, ', ') WITHIN GROUP (ORDER BY D.NM_REF)
                        FROM SP2D_SUMBER_DANA SD
                        LEFT JOIN REF_SUMBER_DANA D
                            ON SD.KD_REF1 = D.KD_REF1
                            AND SD.KD_REF2 = D.KD_REF2
                            AND SD.KD_REF3 = D.KD_REF3
                            AND SD.KD_REF4 = D.KD_REF4
                            AND SD.KD_REF5 = D.KD_REF5
                            AND SD.KD_REF6 = D.KD_REF6
                        WHERE SD.SP2D_ID = d.id_sp2d AND SD.DELETED_AT IS NULL
                    )
                ) AS sumber_dana,
    
                COALESCE(
                    c.nm_belanja,
                    (
                        SELECT LISTAGG(R.NM_REKENING, ', ') WITHIN GROUP (ORDER BY R.NM_REKENING)
                        FROM SP2D_REKENING SR
                        LEFT JOIN REF_REKENING R
                            ON SR.KD_REKENING1 = R.KD_REKENING1
                            AND SR.KD_REKENING2 = R.KD_REKENING2
                            AND SR.KD_REKENING3 = R.KD_REKENING3
                            AND SR.KD_REKENING4 = R.KD_REKENING4
                            AND SR.KD_REKENING5 = R.KD_REKENING5
                            AND SR.KD_REKENING6 = R.KD_REKENING6
                        WHERE SR.SP2D_ID = d.id_sp2d
                    )
                ) AS jenis_belanja,
    
                d.nilai_belanja AS jumlah,
                TO_CHAR(d.tanggal_upload, 'YYYY-MM-DD HH24:MI:SS') AS tanggal_upload_formatted
    
            FROM sp2d d
            LEFT JOIN REF_SUMBER_DANA b
                ON d.kd_ref1 = b.kd_ref1
                AND d.kd_ref2 = b.kd_ref2
                AND d.kd_ref3 = b.kd_ref3
                AND d.kd_ref4 = b.kd_ref4
                AND d.kd_ref5 = b.kd_ref5
                AND d.kd_ref6 = b.kd_ref6
            LEFT JOIN ref_jenis_belanja c
                ON d.kd_belanja1 = c.kd_ref1
                AND d.kd_belanja2 = c.kd_ref2
                AND d.kd_belanja3 = c.kd_ref3
    
            WHERE
                  d.tahun    = ?
              AND d.kd_opd1  = ?
              AND d.kd_opd2  = ?
              AND d.kd_opd3  = ?
              AND d.kd_opd4  = ?
              AND d.kd_opd5  = ?
              AND d.diterima IS NOT NULL
    
            ORDER BY d.diterima
        ";
    
        $result = DB::select($sql, [
            $params['tahun'],
            $params['kd_opd1'],
            $params['kd_opd2'],
            $params['kd_opd3'],
            $params['kd_opd4'],
            $params['kd_opd5'],
        ]);
    
        return response()->json([
            'status' => true,
            'params' => $params,
            'data'   => $result,
        ]);
    }
    

    public function export_excel(Request $request)
    {
        $tahun = $request->tahun;
    
        $skpd = [
            'KD_OPD1' => $request->kd_opd1,
            'KD_OPD2' => $request->kd_opd2,
            'KD_OPD3' => $request->kd_opd3,
            'KD_OPD4' => $request->kd_opd4,
            'KD_OPD5' => $request->kd_opd5,
            'NM_OPD' => $request->nm_opd,
        ];
    
        // --- QUERY LENGKAP ---
        $sql = "
            SELECT 
                TRUNC(d.diterima) AS tanggal,
    
                COALESCE(
                    b.NM_REF,
                    (
                        SELECT LISTAGG(D.NM_REF, ', ') WITHIN GROUP (ORDER BY D.NM_REF)
                        FROM SP2D_SUMBER_DANA SD
                        LEFT JOIN REF_SUMBER_DANA D
                            ON SD.KD_REF1 = D.KD_REF1
                            AND SD.KD_REF2 = D.KD_REF2
                            AND SD.KD_REF3 = D.KD_REF3
                            AND SD.KD_REF4 = D.KD_REF4
                            AND SD.KD_REF5 = D.KD_REF5
                            AND SD.KD_REF6 = D.KD_REF6
                        WHERE SD.SP2D_ID = d.id_sp2d AND SD.DELETED_AT IS NULL
                    )
                ) AS sumber_dana,
    
                COALESCE(
                    c.nm_belanja,
                    (
                        SELECT LISTAGG(R.NM_REKENING, ', ') WITHIN GROUP (ORDER BY R.NM_REKENING)
                        FROM SP2D_REKENING SR
                        LEFT JOIN REF_REKENING R
                            ON SR.KD_REKENING1 = R.KD_REKENING1
                            AND SR.KD_REKENING2 = R.KD_REKENING2
                            AND SR.KD_REKENING3 = R.KD_REKENING3
                            AND SR.KD_REKENING4 = R.KD_REKENING4
                            AND SR.KD_REKENING5 = R.KD_REKENING5
                            AND SR.KD_REKENING6 = R.KD_REKENING6
                        WHERE SR.SP2D_ID = d.id_sp2d
                    )
                ) AS jenis_belanja,
    
                d.nilai_belanja AS jumlah,
                TO_CHAR(d.tanggal_upload, 'YYYY-MM-DD HH24:MI:SS') AS tanggal_upload_formatted
    
            FROM sp2d d
            LEFT JOIN REF_SUMBER_DANA b
                ON d.kd_ref1 = b.kd_ref1
                AND d.kd_ref2 = b.kd_ref2
                AND d.kd_ref3 = b.kd_ref3
                AND d.kd_ref4 = b.kd_ref4
                AND d.kd_ref5 = b.kd_ref5
                AND d.kd_ref6 = b.kd_ref6
            LEFT JOIN ref_jenis_belanja c
                ON d.kd_belanja1 = c.kd_ref1
                AND d.kd_belanja2 = c.kd_ref2
                AND d.kd_belanja3 = c.kd_ref3
    
            WHERE
                  d.tahun    = ?
              AND d.kd_opd1  = ?
              AND d.kd_opd2  = ?
              AND d.kd_opd3  = ?
              AND d.kd_opd4  = ?
              AND d.kd_opd5  = ?
              AND d.diterima IS NOT NULL
    
            ORDER BY d.diterima
        ";
    
        $data = DB::select($sql, [
            $tahun,
            $request->kd_opd1,
            $request->kd_opd2,
            $request->kd_opd3,
            $request->kd_opd4,
            $request->kd_opd5,
        ]);
    
        return Excel::download(
            new LaporanDaftarBelanjaSKPDExport($data, $skpd, $tahun),
            "Daftar_Belanja_SKPD_{$tahun}.xlsx"
        );
    }
    

    public function export_pdf(Request $request)
    {
        $params = $request->only([
            'tahun', 'kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'
        ]);

        // Validasi parameter wajib
        foreach ($params as $key => $value) {
            if (!$value) {
                return response()->json([
                    'status'  => false,
                    'message' => "Parameter {$key} wajib diisi",
                ], 400);
            }
        }

        // ------------------------------------------
        //  QUERY SELECT sama persis dengan CI3
        // ------------------------------------------
        $sql = "
            SELECT 
                TRUNC(d.diterima) AS tanggal,

                COALESCE(
                    b.NM_REF,
                    (
                        SELECT LISTAGG(D.NM_REF, ', ') WITHIN GROUP (ORDER BY D.NM_REF)
                        FROM SP2D_SUMBER_DANA SD
                        LEFT JOIN REF_SUMBER_DANA D
                            ON SD.KD_REF1 = D.KD_REF1
                            AND SD.KD_REF2 = D.KD_REF2
                            AND SD.KD_REF3 = D.KD_REF3
                            AND SD.KD_REF4 = D.KD_REF4
                            AND SD.KD_REF5 = D.KD_REF5
                            AND SD.KD_REF6 = D.KD_REF6
                        WHERE SD.SP2D_ID = d.id_sp2d AND SD.DELETED_AT IS NULL
                    )
                ) AS sumber_dana,

                COALESCE(
                    c.nm_belanja,
                    (
                        SELECT LISTAGG(R.NM_REKENING, ', ') WITHIN GROUP (ORDER BY R.NM_REKENING)
                        FROM SP2D_REKENING SR
                        LEFT JOIN REF_REKENING R
                            ON SR.KD_REKENING1 = R.KD_REKENING1
                            AND SR.KD_REKENING2 = R.KD_REKENING2
                            AND SR.KD_REKENING3 = R.KD_REKENING3
                            AND SR.KD_REKENING4 = R.KD_REKENING4
                            AND SR.KD_REKENING5 = R.KD_REKENING5
                            AND SR.KD_REKENING6 = R.KD_REKENING6
                        WHERE SR.SP2D_ID = d.id_sp2d
                    )
                ) AS jenis_belanja,

                d.nilai_belanja AS jumlah,
                TO_CHAR(d.tanggal_upload, 'YYYY-MM-DD HH24:MI:SS') AS tanggal_upload_formatted

            FROM sp2d d
            LEFT JOIN REF_SUMBER_DANA b
                ON d.kd_ref1 = b.kd_ref1
                AND d.kd_ref2 = b.kd_ref2
                AND d.kd_ref3 = b.kd_ref3
                AND d.kd_ref4 = b.kd_ref4
                AND d.kd_ref5 = b.kd_ref5
                AND d.kd_ref6 = b.kd_ref6
            LEFT JOIN ref_jenis_belanja c
                ON d.kd_belanja1 = c.kd_ref1
                AND d.kd_belanja2 = c.kd_ref2
                AND d.kd_belanja3 = c.kd_ref3

            WHERE d.tahun   = ?
              AND d.kd_opd1 = ?
              AND d.kd_opd2 = ?
              AND d.kd_opd3 = ?
              AND d.kd_opd4 = ?
              AND d.kd_opd5 = ?
              AND d.diterima IS NOT NULL

            ORDER BY d.diterima
        ";

        $result = DB::select($sql, [
            $params['tahun'],
            $params['kd_opd1'],
            $params['kd_opd2'],
            $params['kd_opd3'],
            $params['kd_opd4'],
            $params['kd_opd5'],
        ]);

        // ------------------------------------------
        // Ambil nama SKPD seperti CI3
        // ------------------------------------------
        $skpd = DB::table('REF_OPD')
            ->where([
                'KD_OPD1' => $params['kd_opd1'],
                'KD_OPD2' => $params['kd_opd2'],
                'KD_OPD3' => $params['kd_opd3'],
                'KD_OPD4' => $params['kd_opd4'],
                'KD_OPD5' => $params['kd_opd5'],
                'HIDDEN'  => 0
            ])
            ->first();

        // ------------------------------------------
        // Load Blade + DOMPDF
        // ------------------------------------------
        $pdf = Pdf::loadView('laporan.daftar_belanja_per_skpd.cetak_pdf', [
            'result' => $result,
            'tahun'  => $params['tahun'],
            'skpd'   => $skpd
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('laporan.pdf');
        // return $pdf->download('laporan.pdf');
    }
}
