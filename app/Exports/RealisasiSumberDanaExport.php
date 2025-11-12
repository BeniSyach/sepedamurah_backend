<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RealisasiSumberDanaExport implements FromView
{
    protected $result;
    protected $tahun;

    public function __construct($result, $tahun)
    {
        $this->result = $result;
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        return view('laporan.realisasi_sumber_dana.cetak_excel', [
            'result' => $this->result,
            'tahun' => $this->tahun
        ]);
    }
}
