<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatRekeningModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dat_rekening';

    // Primary key composite → non-incrementing
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    // Fillable fields (huruf kecil)
    protected $fillable = [
        'tahun_rek',
        'kd_rek1',
        'kd_rek2',
        'kd_rek3',
        'kd_rek4',
        'kd_rek5',
        'kd_rek6',
        'nm_rekening',
        'status_rek',
    ];

    /**
     * Helper untuk cari berdasarkan composite key
     */
    public static function findByKeys($tahun, $kd1, $kd2, $kd3, $kd4, $kd5 = null, $kd6 = null)
    {
        return self::where('tahun_rek', $tahun)
            ->where('kd_rek1', $kd1)
            ->where('kd_rek2', $kd2)
            ->where('kd_rek3', $kd3)
            ->where('kd_rek4', $kd4)
            ->where('kd_rek5', $kd5)
            ->where('kd_rek6', $kd6)
            ->first();
    }
}
