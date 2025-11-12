<?php

namespace App\Helpers;

class Terbilang
{
    public function kalimat($angka)
    {
        // versi sederhana
        $f = new \NumberFormatter("id", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($angka)) . ' rupiah';
    }

     /**
     * Format tanggal ke format Indonesia (contoh: 10 November 2025)
     */
    public static function tglIndo(string $tanggal): string
    {
        if (empty($tanggal)) {
            return '';
        }

        $pecahkan = explode('-', $tanggal);
        if (count($pecahkan) !== 3) {
            return $tanggal;
        }

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        return (int)$pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
    }
}
