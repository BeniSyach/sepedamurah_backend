<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatasWaktuModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'batas_waktu';
    protected $primaryKey = 'id';
    public $incrementing = false; // ID di-generate oleh trigger Oracle
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'hari',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'waktu_awal',
        'waktu_akhir',
        'keterangan',
        'istirahat_awal',
        'istirahat_akhir',
    ];

    // Mapping kolom timestamp
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getSkpdAttribute()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }

    // public function skpd()
    // {
    //     return SKPDModel::where('kd_opd1', $this->kd_opd1)
    //         ->where('kd_opd2', $this->kd_opd2)
    //         ->where('kd_opd3', $this->kd_opd3)
    //         ->where('kd_opd4', $this->kd_opd4)
    //         ->where('kd_opd5', $this->kd_opd5)
    //         ->first();
    // }
}
