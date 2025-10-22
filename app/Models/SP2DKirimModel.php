<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SP2DKirimModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    
    // Nama tabel lengkap (schema + nama tabel)
    protected $table = 'SP2D_KIRIM';

    // Primary key
    protected $primaryKey = 'ID';

    public $incrementing = false; // Karena Oracle pakai sequence
    protected $keyType = 'int';

    // Aktifkan timestamps Laravel
    public $timestamps = true;

    // Kolom yang diperlakukan sebagai tanggal
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'TANGGAL_UPLOAD',
        'TGL_TTE',
        'TGL_KIRIM_KEBANK',
    ];

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'TAHUN',
        'ID_BERKAS',
        'ID_PENERIMA',
        'NAMA_PENERIMA',
        'ID_OPERATOR',
        'NAMA_OPERATOR',
        'NAMAFILE',
        'NAMA_FILE_ASLI',
        'TANGGAL_UPLOAD',
        'KETERANGAN',
        'DITERIMA',
        'DITOLAK',
        'TTE',
        'STATUS',
        'TGL_TTE',
        'ALASAN_TOLAK',
        'TGL_KIRIM_KEBANK',
        'ID_PENANDATANGAN',
        'NAMA_PENANDATANGAN',
        'FILE_TTE',
        'KD_OPD1',
        'KD_OPD2',
        'KD_OPD3',
        'KD_OPD4',
        'KD_OPD5',
        'PUBLISH',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
