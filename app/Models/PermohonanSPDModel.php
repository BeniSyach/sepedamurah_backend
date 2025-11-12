<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermohonanSPDModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'permohonan_spd';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'id',
        'id_pengirim',
        'nama_pengirim',
        'id_operator',
        'nama_operator',
        'jenis_berkas',
        'nama_file',
        'nama_file_asli',
        'tanggal_upload',
        'kode_file',
        'diterima',
        'ditolak',
        'alasan_tolak',
        'deleted',
        'proses',
        'supervisor_proses',
        'date_created',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'kd_opd6',
        'kd_opd7',
        'kd_opd8',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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
     * Relasi ke User sebagai pengirim
     */
    public function pengirim()
    {
        return $this->belongsTo(User::class, 'id_pengirim', 'id');
    }

    /**
     * Relasi ke User sebagai operator
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator', 'id');
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
