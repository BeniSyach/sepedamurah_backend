<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengembalianModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'DATA_PENGEMBALIAN';
    protected $primaryKey = 'NO_STS';
    public $incrementing = false; // Karena NO_STS bukan auto increment
    public $timestamps = true; // agar created_at & updated_at digunakan
    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';
    const DELETED_AT = 'DELETED_AT';

    protected $fillable = [
        'NO_STS',
        'NIK',
        'NAMA',
        'ALAMAT',
        'TAHUN',
        'KD_REK1',
        'KD_REK2',
        'KD_REK3',
        'KD_REK4',
        'KD_REK5',
        'KD_REK6',
        'NM_REKENING',
        'KETERANGAN',
        'KD_OPD1',
        'KD_OPD2',
        'KD_OPD3',
        'KD_OPD4',
        'KD_OPD5',
        'JML_PENGEMBALIAN',
        'TGL_REKAM',
        'JML_YG_DISETOR',
        'TGL_SETOR',
        'NIP_PEREKAM',
        'KODE_PENGESAHAN',
        'KODE_CABANG',
        'NAMA_CHANNEL',
        'STATUS_PEMBAYARAN_PAJAK',
    ];

    protected $dates = [
        'TGL_REKAM',
        'TGL_SETOR',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];
}
