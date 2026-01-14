<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanRekGajiSkpdModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'laporan_rek_gaji_skpd';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5',
        'rek_gaji_id', 'user_id',
        'id_operator', 'nama_operator',
        'tahun', 'proses', 'supervisor_proses', 'file',
        'diterima', 'ditolak', 'alasan_tolak',
        'created_at', 'updated_at', 'deleted_at'
    ];

    /* ===============================
     * RELASI
     * =============================== */

    // Relasi ke master rekonsiliasi gaji
    public function rekGaji()
    {
        return $this->belongsTo(
            RefRekonsiliasiGajiSkpdModel::class,
            'rek_gaji_id',
            'id'
        );
    }

    // User pengaju
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Operator / verifikator
    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator', 'id');
    }

    /* ===============================
     * ACCESSOR
     * =============================== */

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
