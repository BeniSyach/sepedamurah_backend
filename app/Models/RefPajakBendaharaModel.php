<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefPajakBendaharaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_pajak_bendahara';
    protected $primaryKey = 'id';

    // ID diisi oleh sequence + trigger Oracle
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nm_pajak_bendahara',
    ];

    // === Mapping kolom tanggal (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];
}
