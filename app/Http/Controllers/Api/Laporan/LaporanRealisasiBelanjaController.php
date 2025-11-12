<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Exports\RealisasiBelanjaExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanRealisasiBelanjaController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));

        $data = DB::connection('oracle')->select("
            SELECT 
                KD_REF1, 
                KD_REF2, 
                KD_REF3, 
                NM_BELANJA AS JENIS_BELANJA,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 1, TOTAL_NILAI, 0)) AS BELANJA_JAN,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 2, TOTAL_NILAI, 0)) AS BELANJA_FEB,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 3, TOTAL_NILAI, 0)) AS BELANJA_MAR,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 4, TOTAL_NILAI, 0)) AS BELANJA_APR,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 5, TOTAL_NILAI, 0)) AS BELANJA_MAY,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 6, TOTAL_NILAI, 0)) AS BELANJA_JUN,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 7, TOTAL_NILAI, 0)) AS BELANJA_JUL,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 8, TOTAL_NILAI, 0)) AS BELANJA_AUG,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA), 9, TOTAL_NILAI, 0)) AS BELANJA_SEP,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA),10, TOTAL_NILAI, 0)) AS BELANJA_OCT,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA),11, TOTAL_NILAI, 0)) AS BELANJA_NOV,
                SUM(DECODE(EXTRACT(MONTH FROM DITERIMA),12, TOTAL_NILAI, 0)) AS BELANJA_DEC
            FROM (
                -- SQL 1
                SELECT 
                    s.KD_BELANJA1 AS KD_REF1, 
                    s.KD_BELANJA2 AS KD_REF2, 
                    s.KD_BELANJA3 AS KD_REF3, 
                    rjb.NM_BELANJA,
                    s.DITERIMA,
                    SUM(s.NILAI_BELANJA) AS TOTAL_NILAI
                FROM SP2D s
                LEFT JOIN REF_JENIS_BELANJA rjb 
                    ON s.KD_BELANJA1 = rjb.KD_REF1
                    AND s.KD_BELANJA2 = rjb.KD_REF2
                    AND s.KD_BELANJA3 = rjb.KD_REF3
                WHERE s.DITERIMA IS NOT NULL 
                    AND s.KD_BELANJA1 IS NOT NULL
                    AND s.TAHUN = :tahun
                GROUP BY 
                    s.KD_BELANJA1, 
                    s.KD_BELANJA2, 
                    s.KD_BELANJA3,
                    rjb.NM_BELANJA,
                    s.DITERIMA
            
                UNION ALL
            
                -- SQL 2
                SELECT 
                    NVL(rjb.KD_REF1, s.KD_BELANJA1) AS KD_REF1,
                    NVL(rjb.KD_REF2, s.KD_BELANJA2) AS KD_REF2,
                    NVL(rjb.KD_REF3, s.KD_BELANJA3) AS KD_REF3,
                    NVL(rjb.NM_BELANJA, rjb2.NM_BELANJA) AS NM_BELANJA,
                    s.DITERIMA,
                    NVL(SUM(sr.NILAI), 0) AS TOTAL_NILAI
                FROM SP2D s
                LEFT JOIN SP2D_REKENING sr 
                    ON sr.SP2D_ID = s.ID_SP2D
                LEFT JOIN REF_JENIS_BELANJA rjb 
                    ON sr.KD_REKENING1 = rjb.KD_REF1
                    AND sr.KD_REKENING2 = rjb.KD_REF2
                    AND sr.KD_REKENING3 = rjb.KD_REF3
                LEFT JOIN REF_JENIS_BELANJA rjb2
                    ON s.KD_BELANJA1 = rjb2.KD_REF1
                    AND s.KD_BELANJA2 = rjb2.KD_REF2
                    AND s.KD_BELANJA3 = rjb2.KD_REF3
                WHERE s.DITERIMA IS NOT NULL  
                    AND NVL(rjb.KD_REF1, s.KD_BELANJA1) IS NOT NULL 
                    AND s.TAHUN = :tahun
                GROUP BY 
                    NVL(rjb.KD_REF1, s.KD_BELANJA1), 
                    NVL(rjb.KD_REF2, s.KD_BELANJA2), 
                    NVL(rjb.KD_REF3, s.KD_BELANJA3), 
                    NVL(rjb.NM_BELANJA, rjb2.NM_BELANJA),
                    s.DITERIMA
            ) combined
            GROUP BY 
                KD_REF1, 
                KD_REF2, 
                KD_REF3, 
                NM_BELANJA
            ORDER BY 
                KD_REF1, 
                KD_REF2, 
                KD_REF3
        ", ['tahun' => $tahun]);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function export_excel(Request $request)
    {
        // Ambil input tahun & bulan, default ke sekarang
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan', date('m'));

        // Query data dari database
        $query = DB::select("
        SELECT 
            KD_REF1, 
            KD_REF2, 
            KD_REF3, 
            NM_BELANJA AS JENIS_BELANJA,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 1 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JAN,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 2 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_FEB,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 3 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_MAR,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 4 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_APR,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 5 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_MAY,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 6 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JUN,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 7 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JUL,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 8 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_AUG,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 9 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_SEP,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 10 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_OCT,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 11 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_NOV,
            SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 12 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_DEC
        FROM (
            SELECT 
                s.KD_BELANJA1 AS KD_REF1, 
                s.KD_BELANJA2 AS KD_REF2, 
                s.KD_BELANJA3 AS KD_REF3, 
                rjb.NM_BELANJA,
                s.DITERIMA,
                SUM(s.NILAI_BELANJA) AS TOTAL_NILAI
            FROM SP2D s
            LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb 
                ON s.KD_BELANJA1 = rjb.KD_REF1
                AND s.KD_BELANJA2 = rjb.KD_REF2
                AND s.KD_BELANJA3 = rjb.KD_REF3
            WHERE s.DITERIMA IS NOT NULL 
                AND s.KD_BELANJA1 IS NOT NULL
            GROUP BY 
                s.KD_BELANJA1, 
                s.KD_BELANJA2, 
                s.KD_BELANJA3,
                rjb.NM_BELANJA,
                s.DITERIMA

            UNION ALL

            SELECT 
                COALESCE(rjb.KD_REF1, s.KD_BELANJA1) AS KD_REF1,
                COALESCE(rjb.KD_REF2, s.KD_BELANJA2) AS KD_REF2,
                COALESCE(rjb.KD_REF3, s.KD_BELANJA3) AS KD_REF3,
                COALESCE(rjb.NM_BELANJA, rjb2.NM_BELANJA) AS NM_BELANJA,
                s.DITERIMA,
                COALESCE(SUM(sr.NILAI), 0) AS TOTAL_NILAI
            FROM SP2D s
            LEFT JOIN PENGEMBALIAN.SP2D_REKENING sr 
                ON sr.SP2D_ID = s.ID_SP2D
            LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb 
                ON sr.KD_REKENING1 = rjb.KD_REF1
                AND sr.KD_REKENING2 = rjb.KD_REF2
                AND sr.KD_REKENING3 = rjb.KD_REF3
            LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb2
                ON s.KD_BELANJA1 = rjb2.KD_REF1
                AND s.KD_BELANJA2 = rjb2.KD_REF2
                AND s.KD_BELANJA3 = rjb2.KD_REF3
            WHERE s.DITERIMA IS NOT NULL  
                AND COALESCE(rjb.KD_REF1, s.KD_BELANJA1) IS NOT NULL 
                AND s.TAHUN = ?
            GROUP BY 
                COALESCE(rjb.KD_REF1, s.KD_BELANJA1), 
                COALESCE(rjb.KD_REF2, s.KD_BELANJA2), 
                COALESCE(rjb.KD_REF3, s.KD_BELANJA3), 
                COALESCE(rjb.NM_BELANJA, rjb2.NM_BELANJA),
                s.DITERIMA
        ) combined
        GROUP BY 
            KD_REF1, 
            KD_REF2, 
            KD_REF3, 
            NM_BELANJA
        ORDER BY 
            KD_REF1, 
            KD_REF2, 
            KD_REF3
    ", [$tahun]);

             $data = [
                'result' => $query,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ];
    

        // Export ke Excel
        return Excel::download(
            new RealisasiBelanjaExport($data, $tahun, $bulan),
            "Laporan_Realisasi_Belanja_{$tahun}_{$bulan}.xlsx"
        );
    }

    public function export_pdf(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan', date('m'));
        // Query konversi dari versi CI3
        $query = DB::select("
            SELECT 
                KD_REF1, 
                KD_REF2, 
                KD_REF3, 
                NM_BELANJA AS JENIS_BELANJA,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 1 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JAN,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 2 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_FEB,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 3 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_MAR,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 4 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_APR,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 5 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_MAY,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 6 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JUN,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 7 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_JUL,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 8 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_AUG,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 9 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_SEP,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 10 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_OCT,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 11 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_NOV,
                SUM(CASE WHEN EXTRACT(MONTH FROM DITERIMA) = 12 THEN TOTAL_NILAI ELSE 0 END) AS BELANJA_DEC
            FROM (
                SELECT 
                    s.KD_BELANJA1 AS KD_REF1, 
                    s.KD_BELANJA2 AS KD_REF2, 
                    s.KD_BELANJA3 AS KD_REF3, 
                    rjb.NM_BELANJA,
                    s.DITERIMA,
                    SUM(s.NILAI_BELANJA) AS TOTAL_NILAI
                FROM SP2D s
                LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb 
                    ON s.KD_BELANJA1 = rjb.KD_REF1
                    AND s.KD_BELANJA2 = rjb.KD_REF2
                    AND s.KD_BELANJA3 = rjb.KD_REF3
                WHERE s.DITERIMA IS NOT NULL 
                    AND s.KD_BELANJA1 IS NOT NULL
                GROUP BY 
                    s.KD_BELANJA1, 
                    s.KD_BELANJA2, 
                    s.KD_BELANJA3,
                    rjb.NM_BELANJA,
                    s.DITERIMA

                UNION ALL

                SELECT 
                    COALESCE(rjb.KD_REF1, s.KD_BELANJA1) AS KD_REF1,
                    COALESCE(rjb.KD_REF2, s.KD_BELANJA2) AS KD_REF2,
                    COALESCE(rjb.KD_REF3, s.KD_BELANJA3) AS KD_REF3,
                    COALESCE(rjb.NM_BELANJA, rjb2.NM_BELANJA) AS NM_BELANJA,
                    s.DITERIMA,
                    COALESCE(SUM(sr.NILAI), 0) AS TOTAL_NILAI
                FROM SP2D s
                LEFT JOIN PENGEMBALIAN.SP2D_REKENING sr 
                    ON sr.SP2D_ID = s.ID_SP2D
                LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb 
                    ON sr.KD_REKENING1 = rjb.KD_REF1
                    AND sr.KD_REKENING2 = rjb.KD_REF2
                    AND sr.KD_REKENING3 = rjb.KD_REF3
                LEFT JOIN PENGEMBALIAN.REF_JENIS_BELANJA rjb2
                    ON s.KD_BELANJA1 = rjb2.KD_REF1
                    AND s.KD_BELANJA2 = rjb2.KD_REF2
                    AND s.KD_BELANJA3 = rjb2.KD_REF3
                WHERE s.DITERIMA IS NOT NULL  
                    AND COALESCE(rjb.KD_REF1, s.KD_BELANJA1) IS NOT NULL 
                    AND s.TAHUN = ?
                GROUP BY 
                    COALESCE(rjb.KD_REF1, s.KD_BELANJA1), 
                    COALESCE(rjb.KD_REF2, s.KD_BELANJA2), 
                    COALESCE(rjb.KD_REF3, s.KD_BELANJA3), 
                    COALESCE(rjb.NM_BELANJA, rjb2.NM_BELANJA),
                    s.DITERIMA
            ) combined
            GROUP BY 
                KD_REF1, 
                KD_REF2, 
                KD_REF3, 
                NM_BELANJA
            ORDER BY 
                KD_REF1, 
                KD_REF2, 
                KD_REF3
        ", [$tahun]);

        // Siapkan data untuk view PDF
        $data = [
            'result' => $query,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('laporan.realisasi_belanja.cetak_pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("Laporan_Belanja_{$tahun}_{$bulan}.pdf");
    }
}
