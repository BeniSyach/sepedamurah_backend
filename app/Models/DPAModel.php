<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DPAModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_dpa';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nm_dpa',
    ];

    // === Mapping kolom tanggal (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];
}
