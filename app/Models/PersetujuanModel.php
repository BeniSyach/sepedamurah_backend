<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PersetujuanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'aggrement';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'konten',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Hook untuk auto-generate ID dari sequence Oracle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ambil ID dari sequence Oracle jika belum diisi
            if (empty($model->id)) {
                $model->id = DB::selectOne('SELECT no_aggrement.NEXTVAL AS id FROM dual')->id;
            }
        });
    }
}
