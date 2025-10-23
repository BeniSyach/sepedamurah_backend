<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanFungsionalModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'fungsional';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena Oracle pakai trigger dan sequence
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_pengirim',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'nama_pengirim',
        'id_operator',
        'nama_operator',
        'jenis_berkas',
        'nama_file',
        'nama_file_asli',
        'tanggal_upload',
        'kode_file',
        'tahun',
        'diterima',
        'ditolak',
        'alasan_tolak',
        'proses',
        'supervisor_proses',
        'date_created',
        'berkas_tte',
    ];

    // Mapping timestamp Laravel
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'tanggal_upload',
        'diterima',
        'ditolak',
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Contoh relasi ke model lain (jika ada)
     */
    public function pengirim()
    {
        return $this->belongsTo(User::class, 'id_pengirim');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    public function getSkpdAttribute()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
