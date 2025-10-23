<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class JenisSPMModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_jenis_spm';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'kategori',
        'nama_berkas',
        'status_penerimaan',
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Ambil nilai ID dari sequence Oracle otomatis sebelum insert
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto generate ID dari sequence Oracle jika belum diisi
            if (empty($model->id)) {
                $model->id = DB::selectOne('SELECT seq_ref_jenis_spm.NEXTVAL AS id FROM dual')->id;
            }

            // Isi tanggal pembuatan jika belum ada
            if (empty($model->date_created)) {
                $model->date_created = now();
            }
        });
    }

    /**
     * Scope untuk ambil data aktif (status_penerimaan = 1)
     */
    public function scopeAktif($query)
    {
        return $query->where('status_penerimaan', 1);
    }
}
