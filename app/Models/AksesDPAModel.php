<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesDPAModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'akses_dpa';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'dpa_id',
        'tahun',
    ];

    // Mapping kolom timestamp (lowercase)
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];

    /**
     * Relasi ke tabel Ref DPA
     * 1 akses_dpa → 1 DPA
     */
    public function dpa()
    {
        return $this->belongsTo(DPAModel::class, 'dpa_id', 'id');
    }

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
