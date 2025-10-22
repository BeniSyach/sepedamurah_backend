<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogTTEModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'TTE_HISTORY';
    protected $primaryKey = 'id';
    public $incrementing = false; // Karena Oracle pakai trigger dan sequence
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_berkas',
        'kategori',
        'tte',
        'status',
        'tgl_tte',
        'keterangan',
        'message',
        'id_penandatangan',
        'nama_penandatangan',
        'date_created',
    ];

    // Mapping kolom timestamp Laravel
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'tgl_tte',
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
