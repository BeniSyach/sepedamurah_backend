<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanDPAModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'laporan_dpa';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5',
        'dpa_id', 'user_id', 'id_operator', 'nama_operator',
        'tahun', 'proses', 'supervisor_proses', 'file',
        'diterima', 'ditolak', 'alasan_tolak',
        'created_at', 'updated_at', 'deleted_at'
    ];

    // RELASI
    public function dpa()
    {
        return $this->belongsTo(DPAModel::class, 'dpa_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator', 'id');
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
