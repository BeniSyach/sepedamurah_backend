<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BerkasLainModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'surat_surat_lain';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai trigger & sequence
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'tgl_surat',
        'nama_file_asli',
        'nama_dokumen',
        'status_tte',
        'file_sdh_tte',
        'users_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Mapping kolom timestamp di Oracle
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'tgl_surat',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relasi ke tabel Users (jika ada)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
