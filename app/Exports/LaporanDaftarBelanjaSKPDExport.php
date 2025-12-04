<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanDaftarBelanjaSKPDExport implements FromView
{
    protected $data;
    protected $skpd;
    protected $tahun;

    public function __construct($data, $skpd, $tahun)
    {
        $this->data = $data;
        $this->skpd = $skpd;
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        return view('laporan.daftar_belanja_per_skpd.cetak_excel', [
            'data'  => $this->data,
            'skpd'  => $this->skpd,
            'tahun' => $this->tahun,
        ]);
    }
}
