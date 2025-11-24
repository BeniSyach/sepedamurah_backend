<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sp2dModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'sp2d';
    protected $primaryKey = 'id_sp2d';
    public $incrementing = false;
    protected $keyType = 'int';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'diterima',
        'ditolak',
        'tanggal_upload',
    ];

    public $timestamps = true;

    protected $fillable = [
        'tahun',
        'id_user',
        'nama_user',
        'id_operator',
        'nama_operator',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'nama_file',
        'nama_file_asli',
        'file_tte',
        'tanggal_upload',
        'kode_file',
        'diterima',
        'ditolak',
        'alasan_tolak',
        'proses',
        'supervisor_proses',
        'urusan',
        'kd_ref1',
        'kd_ref2',
        'kd_ref3',
        'kd_ref4',
        'kd_ref5',
        'kd_ref6',
        'no_spm',
        'jenis_berkas',
        'id_berkas',
        'agreement',
        'kd_belanja1',
        'kd_belanja2',
        'kd_belanja3',
        'jenis_belanja',
        'nilai_belanja',
        'status_laporan',
        'deleted_at',
    ];

    public function sp2dkirim()
    {
        return $this->hasMany(SP2DKirimModel::class, 'id_berkas', 'id_sp2d');
    }

    /**
     * Relasi ke user (opsional)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

     /**
     * Relasi ke tabel sp2d_rekening
     * Satu SP2D bisa punya banyak rekening
     */
    public function rekening()
    {
        return $this->hasMany(SP2DRekeningModel::class, 'sp2d_id', 'id_sp2d');
    }

      /**
     * Relasi ke tabel sp2d_sumber_dana
     * Satu SP2D bisa punya banyak sumber dana
     */
    public function sumberDana()
    {
        return $this->hasMany(SP2DSumberDanaModel::class, 'sp2d_id', 'id_sp2d');
    }

    /**
     * (Opsional) Akses total nilai dari semua rekening
     */
    public function getTotalRekeningAttribute()
    {
        return $this->rekening()->sum('nilai');
    }

    /**
     * (Opsional) Akses total nilai dari semua sumber dana
     */
    public function getTotalSumberDanaAttribute()
    {
        return $this->sumberDana()->sum('nilai');
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
