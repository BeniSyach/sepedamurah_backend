<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesRekGajiSkpdModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'akses_rek_gaji_skpd';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'rek_gaji_id',
        'tahun',
    ];

    // Mapping kolom timestamp (lowercase)
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];

    /**
     * Relasi ke tabel Ref Rekonsiliasi Gaji SKPD
     * 1 akses_rek_gaji_skpd → 1 ref_rekonsiliasi_gaji_skpd
     */
    public function rekonsiliasiGajiSkpd()
    {
        return $this->belongsTo(
            RefRekonsiliasiGajiSkpdModel::class,
            'rek_gaji_id',
            'id'
        );
    }

    /**
     * Ambil data SKPD berdasarkan kode OPD
     */
    public function skpd()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
