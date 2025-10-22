<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class KategoriSPMModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'kategori_spm';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Aktifkan timestamps Laravel
    public $timestamps = true;

    protected $fillable = [
        'kategori',
        'status',
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Karena tabel Oracle awal tidak punya kolom ini, 
    // pastikan kolomnya sudah ditambahkan lewat migration
    protected $dates = [
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Mapping agar Laravel tahu nama kolom timestamp
     */
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Boot method untuk ambil sequence Oracle otomatis
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ambil ID dari sequence Oracle jika belum diisi
            if (empty($model->id)) {
                $model->id = DB::selectOne('SELECT pengembalian.seq_kategori_spm.NEXTVAL AS id FROM dual')->id;
            }

            // Isi tanggal jika belum ada
            if (empty($model->date_created)) {
                $model->date_created = now();
            }
        });
    }

    /**
     * Scope untuk ambil data aktif (status = 1)
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 1);
    }
}
