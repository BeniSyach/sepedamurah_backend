<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    }

    public function export_pdf(Request $request)
    {
        
    }
}
