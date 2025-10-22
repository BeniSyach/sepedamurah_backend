<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubKegiatanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_subkegiatan';
    public $incrementing = false;
    protected $primaryKey = null; // karena menggunakan composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_subkeg1',
        'kd_subkeg2',
        'kd_subkeg3',
        'kd_subkeg4',
        'kd_subkeg5',
        'kd_subkeg6',
        'nm_subkegiatan',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Override default save query untuk mendukung composite primary key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_subkeg1', '=', $this->getAttribute('kd_subkeg1'))
            ->where('kd_subkeg2', '=', $this->getAttribute('kd_subkeg2'))
            ->where('kd_subkeg3', '=', $this->getAttribute('kd_subkeg3'))
            ->where('kd_subkeg4', '=', $this->getAttribute('kd_subkeg4'))
            ->where('kd_subkeg5', '=', $this->getAttribute('kd_subkeg5'))
            ->where('kd_subkeg6', '=', $this->getAttribute('kd_subkeg6'));
    }
}
