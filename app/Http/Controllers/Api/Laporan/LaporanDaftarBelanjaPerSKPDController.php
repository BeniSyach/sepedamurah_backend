<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            GROUP BY 
                a.kd_opd1, a.kd_opd2, a.kd_opd3, a.kd_opd4, a.kd_opd5, a.nm_opd
            ORDER BY 
                a.kd_opd1, a.kd_opd2, a.kd_opd3, a.kd_opd4, a.kd_opd5
        ", ['tahun' => $tahun]);

        return response()->json([
            'data' => $data,
        ]);
    }
}
