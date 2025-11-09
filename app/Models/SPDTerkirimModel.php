<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpdTerkirimModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'spd_terkirim';
    protected $primaryKey = 'id';
    public $incrementing = false; // Karena ID di-generate oleh trigger (bukan auto-increment DB)
    protected $keyType = 'int';

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'id',
        'id_berkas',
        'id_penerima',
        'nama_penerima',
        'id_operator',
        'nama_operator',
        'namafile',
        'nama_file_asli',
        'nama_file_lampiran',
        'tanggal_upload',
        'keterangan',
        'paraf_kbud',
        'tgl_paraf',
        'tte',
        'passpharase',
        'status',
        'tgl_tte',
        'id_penandatangan',
        'nama_penandatangan',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'file_tte',
        'publish',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = [
        'tanggal_upload',
        'tgl_paraf',
        'tgl_tte',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function skpd()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }

    public function permohonan()
    {
        return $this->belongsTo(PermohonanSpdModel::class, 'id_berkas', 'id');
    }
}
