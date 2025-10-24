<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengembalianModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'data_pengembalian';
    protected $primaryKey = 'no_sts';
    public $incrementing = false; // karena no_sts bukan auto increment
    public $timestamps = true; // agar created_at & updated_at digunakan
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'no_sts',
        'nik',
        'nama',
        'alamat',
        'tahun',
        'kd_rek1',
        'kd_rek2',
        'kd_rek3',
        'kd_rek4',
        'kd_rek5',
        'kd_rek6',
        'nm_rekening',
        'keterangan',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'jml_pengembalian',
        'tgl_rekam',
        'jml_yg_disetor',
        'tgl_setor',
        'nip_perekam',
        'kode_pengesahan',
        'kode_cabang',
        'nama_channel',
        'status_pembayaran_pajak',
    ];

    protected $dates = [
        'tgl_rekam',
        'tgl_setor',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getStatusBayarAttribute()
    {
        $sisa = $this->jml_pengembalian - $this->jml_yg_disetor;

        if ($sisa <= 0) {
            return 'SDH BAYAR';
        }

        if ($sisa == $this->jml_pengembalian) {
            return 'BLM BAYAR';
        }

        return 'KRG BAYAR';
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
