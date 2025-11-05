<?php

namespace App\Models;

use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;

class LevelRekeningModel extends Eloquent
{
    protected $table = 'level_rekening';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nm_level_rek',
    ];
}
