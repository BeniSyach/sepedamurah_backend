<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Exports\RealisasiSumberDanaExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanRealisasiSumberDanaController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));

        $data = DB::connection('oracle')->select(
            "SELECT 
            COALESCE(a.kd_ref1, b.kd_ref1, c.kd_ref1) AS kd_ref1, 
            COALESCE(a.kd_ref2, b.kd_ref2, c.kd_ref2) AS kd_ref2, 
            COALESCE(a.kd_ref3, b.kd_ref3, c.kd_ref3) AS kd_ref3, 
            COALESCE(a.kd_ref4, b.kd_ref4, c.kd_ref4) AS kd_ref4, 
            COALESCE(a.kd_ref5, b.kd_ref5, c.kd_ref5) AS kd_ref5, 
            COALESCE(a.kd_ref6, b.kd_ref6, c.kd_ref6) AS kd_ref6, 
            d.nm_ref AS nm_sumber, 
            NVL(a.pagu, 0) AS pagu, 
            NVL(a.jumlah_silpa, 0) AS jumlah_silpa, 
            NVL(b.jum_sumber_dana, 0) AS sumber_dana, 
            NVL(c.jum_belanja, 0) AS belanja, 
            NVL(a.jumlah_silpa, 0) + NVL(b.jum_sumber_dana, 0) - NVL(c.jum_belanja, 0) AS sisa
        FROM 
            pagu_sumber_dana a
        FULL OUTER JOIN 
            v_group_sumber_dana b
        ON 
            NVL(a.kd_ref1, 'NULL') = NVL(b.kd_ref1, 'NULL')
            AND NVL(a.kd_ref2, 'NULL') = NVL(b.kd_ref2, 'NULL')
            AND NVL(a.kd_ref3, 'NULL') = NVL(b.kd_ref3, 'NULL')
            AND NVL(a.kd_ref4, 'NULL') = NVL(b.kd_ref4, 'NULL')
            AND NVL(a.kd_ref5, 'NULL') = NVL(b.kd_ref5, 'NULL')
            AND NVL(a.kd_ref6, 'NULL') = NVL(b.kd_ref6, 'NULL')
            AND a.tahun = b.tahun
        FULL OUTER JOIN 
            v_group_sp2d c
        ON 
            NVL(a.kd_ref1, 'NULL') = NVL(c.kd_ref1, 'NULL')
            AND NVL(a.kd_ref2, 'NULL') = NVL(c.kd_ref2, 'NULL')
            AND NVL(a.kd_ref3, 'NULL') = NVL(c.kd_ref3, 'NULL')
            AND NVL(a.kd_ref4, 'NULL') = NVL(c.kd_ref4, 'NULL')
            AND NVL(a.kd_ref5, 'NULL') = NVL(c.kd_ref5, 'NULL')
            AND NVL(a.kd_ref6, 'NULL') = NVL(c.kd_ref6, 'NULL')
            AND a.tahun = c.tahun
        LEFT JOIN 
            ref_sumber_dana d
        ON 
            COALESCE(a.kd_ref1, b.kd_ref1, c.kd_ref1) = d.kd_ref1
            AND COALESCE(a.kd_ref2, b.kd_ref2, c.kd_ref2) = d.kd_ref2
            AND COALESCE(a.kd_ref3, b.kd_ref3, c.kd_ref3) = d.kd_ref3
            AND COALESCE(a.kd_ref4, b.kd_ref4, c.kd_ref4) = d.kd_ref4
            AND COALESCE(a.kd_ref5, b.kd_ref5, c.kd_ref5) = d.kd_ref5
            AND COALESCE(a.kd_ref6, b.kd_ref6, c.kd_ref6) = d.kd_ref6
        WHERE  
            COALESCE(a.tahun, b.tahun, c.tahun) = :tahun
        ORDER BY 
            kd_ref1, kd_ref2, kd_ref3, kd_ref4, kd_ref5, kd_ref6", ['tahun' => $tahun]);

                return response()->json([
                'data' => $data,
            ]);
    }

    public function export_excel(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
    
        // Query sama seperti di CodeIgniter 3
        $sql = "
            SELECT 
                a.kd_ref1, a.kd_ref2, a.kd_ref3, a.kd_ref4, a.kd_ref5, a.kd_ref6,
                d.nm_ref AS nm_sumber,
                a.pagu,
                a.jumlah_silpa,
                NVL(b.jum_sumber_dana, 0) AS sumber_dana,
                NVL(c.jum_belanja, 0) AS belanja,
                a.jumlah_silpa + NVL(b.jum_sumber_dana, 0) - NVL(c.jum_belanja, 0) AS sisa
            FROM pagu_sumber_dana a
            LEFT JOIN v_group_sumber_dana b
                ON NVL(a.kd_ref1, 'NULL') = NVL(b.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(b.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(b.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(b.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(b.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(b.kd_ref6, 'NULL')
                AND a.tahun = b.tahun
            LEFT JOIN v_group_sp2d c
                ON NVL(a.kd_ref1, 'NULL') = NVL(c.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(c.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(c.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(c.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(c.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(c.kd_ref6, 'NULL')
                AND a.tahun = c.tahun
            LEFT JOIN ref_sumber_dana d
                ON NVL(a.kd_ref1, 'NULL') = NVL(d.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(d.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(d.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(d.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(d.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(d.kd_ref6, 'NULL')
            WHERE a.tahun = ?
            ORDER BY 
                a.kd_ref1, a.kd_ref2, a.kd_ref3, a.kd_ref4, a.kd_ref5, a.kd_ref6
        ";

        // Jalankan query
        $result = DB::select($sql, [$tahun]);
    
        // === Export ke Excel ===
        return Excel::download(
            new RealisasiSumberDanaExport($result, $tahun),
            "Laporan_Realisasi_Sumber_Dana_{$tahun}.xlsx"
        );
    }

    public function export_pdf(Request $request)
    {

        $tahun = $request->input('tahun', date('Y'));

        // Query sama seperti di CodeIgniter 3
        $sql = "
            SELECT 
                a.kd_ref1, a.kd_ref2, a.kd_ref3, a.kd_ref4, a.kd_ref5, a.kd_ref6,
                d.nm_ref AS nm_sumber,
                a.pagu,
                a.jumlah_silpa,
                NVL(b.jum_sumber_dana, 0) AS sumber_dana,
                NVL(c.jum_belanja, 0) AS belanja,
                a.jumlah_silpa + NVL(b.jum_sumber_dana, 0) - NVL(c.jum_belanja, 0) AS sisa
            FROM pagu_sumber_dana a
            LEFT JOIN v_group_sumber_dana b
                ON NVL(a.kd_ref1, 'NULL') = NVL(b.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(b.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(b.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(b.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(b.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(b.kd_ref6, 'NULL')
                AND a.tahun = b.tahun
            LEFT JOIN v_group_sp2d c
                ON NVL(a.kd_ref1, 'NULL') = NVL(c.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(c.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(c.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(c.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(c.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(c.kd_ref6, 'NULL')
                AND a.tahun = c.tahun
            LEFT JOIN ref_sumber_dana d
                ON NVL(a.kd_ref1, 'NULL') = NVL(d.kd_ref1, 'NULL')
                AND NVL(a.kd_ref2, 'NULL') = NVL(d.kd_ref2, 'NULL')
                AND NVL(a.kd_ref3, 'NULL') = NVL(d.kd_ref3, 'NULL')
                AND NVL(a.kd_ref4, 'NULL') = NVL(d.kd_ref4, 'NULL')
                AND NVL(a.kd_ref5, 'NULL') = NVL(d.kd_ref5, 'NULL')
                AND NVL(a.kd_ref6, 'NULL') = NVL(d.kd_ref6, 'NULL')
            WHERE a.tahun = ?
            ORDER BY 
                a.kd_ref1, a.kd_ref2, a.kd_ref3, a.kd_ref4, a.kd_ref5, a.kd_ref6
        ";

        // Jalankan query
        $result = DB::select($sql, [$tahun]);

        // Kirim ke view
        $pdf = Pdf::loadView('laporan.realisasi_sumber_dana.cetak_pdf', [
                'result' => $result,
                'tahun' => $tahun
            ])
            ->setPaper('a4', 'landscape');

        // Download PDF
        return $pdf->download("Laporan_Realisasi_Sumber_Dana_{$tahun}.pdf");
    }
}