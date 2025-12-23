<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatRekeningModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dat_rekening';

    // Primary key composite → non-incrementing
    protected $primaryKey = null;
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
    public static function findByKeys(
        $tahun,
        $kd1,
        $kd2,
        $kd3,
        $kd4,
        $kd5 = null,
        $kd6 = null
    ) {
        $query = self::whereRaw('TRIM(tahun_rek) = ?', [trim($tahun)])
            ->whereRaw('TRIM(kd_rek1) = ?', [trim($kd1)])
            ->whereRaw('TRIM(kd_rek2) = ?', [trim($kd2)])
            ->whereRaw('TRIM(kd_rek3) = ?', [trim($kd3)])
            ->whereRaw('TRIM(kd_rek4) = ?', [trim($kd4)]);
    
        // kd_rek5
        if ($kd5 !== null && trim($kd5) !== '') {
            $query->whereRaw('TRIM(kd_rek5) = ?', [trim($kd5)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek5')
                  ->orWhereRaw("TRIM(kd_rek5) = ''");
            });
        }
    
        // kd_rek6
        if ($kd6 !== null && trim($kd6) !== '') {
            $query->whereRaw('TRIM(kd_rek6) = ?', [trim($kd6)]);
        } else {
            $query->where(function ($q) {
                $q->whereNull('kd_rek6')
                  ->orWhereRaw("TRIM(kd_rek6) = ''");
            });
        }
    
        return $query->first();
    }
    
}
