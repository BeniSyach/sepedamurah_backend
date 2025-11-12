<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RekapPengembalianExport implements FromView
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
        return view('pengembalian_admin.cetak_excel', [
            'data_pengembalian' => $this->data,
            'tahun' => $this->tahun,
            'bulan' => $this->bulan
        ]);
    }
}
