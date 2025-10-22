<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RealisasiSumberDanaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'SUMBER_DANA';
    protected $primaryKey = 'id';
    public $incrementing = true; // karena ID di-generate dari sequence
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'kd_ref1', 'kd_ref2', 'kd_ref3', 'kd_ref4', 'kd_ref5', 'kd_ref6',
        'nm_sumber', 'tgl_diterima', 'tahun', 'jumlah_sumber', 'keterangan', 'keterangan_2'
    ];

    protected $casts = [
        'tgl_diterima' => 'datetime',
        'jumlah_sumber' => 'float',
        'keterangan' => 'integer',
    ];

    protected $dates = [
        'tgl_diterima', 'created_at', 'updated_at', 'deleted_at'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Auto-generate ID sebelum insert
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = DB::selectOne('SELECT pengembalian.no_sumber_dana.NEXTVAL AS id FROM dual')->id;
            }
        });
    }
}
