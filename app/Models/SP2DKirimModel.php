<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SP2DKirimModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'sp2d_kirim';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'tanggal_upload',
        'tgl_tte',
        'tgl_kirim_kebank',
    ];

    protected $fillable = [
        'tahun',
        'id_berkas',
        'id_penerima',
        'nama_penerima',
        'id_operator',
        'nama_operator',
        'namafile',
        'nama_file_asli',
        'tanggal_upload',
        'keterangan',
        'diterima',
        'ditolak',
        'tte',
        'status',
        'tgl_tte',
        'alasan_tolak',
        'tgl_kirim_kebank',
        'id_penandatangan',
        'nama_penandatangan',
        'file_tte',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'publish',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    
    public function sp2dPemohon()
    {
        return $this->belongsTo(Sp2dModel::class, 'id_berkas', 'id_sp2d');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'id_penerima');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator');
    }

    public function penandatangan()
    {
        return $this->belongsTo(User::class, 'id_penandatangan');
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
