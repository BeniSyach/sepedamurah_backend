<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RealisasiBelanjaExport implements FromView
{
    protected $data;
    protected $tahun;
    protected $bulan;

    public function __construct($data, $tahun, $bulan)
    {
        $this->data = $data;
        $this->tahun = $tahun;
        $this->bulan = $bulan;
    }

    public function view(): View
    {
        return view('laporan.realisasi_belanja.cetak_excel', [
            'result' => $this->data,
            'tahun' => $this->tahun,
            'bulan' => $this->bulan,
        ]);
    }
}
