<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BesaranUPSKPDModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'pagu_up';
    protected $primaryKey = 'id';
    public $incrementing = false; // Karena ID di-generate dari trigger (NO_IDUP)
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'tahun',
        'pagu',
        'up_kkpd',
    ];

    // Kolom timestamps Oracle biasanya DATE, bukan TIMESTAMP
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Jika kamu ingin default value otomatis
    protected $attributes = [
        'pagu' => 0,
    ];

    /**
     * Relasi ke SKPD (kebalikan dari SKPDModel->besaranUp)
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
