<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KegiatanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_kegiatan';
    public $incrementing = false;
    protected $primaryKey = null; // karena pakai composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_keg1',
        'kd_keg2',
        'kd_keg3',
        'kd_keg4',
        'kd_keg5',
        'nm_kegiatan',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Override default save query to support composite primary key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_keg1', '=', $this->getAttribute('kd_keg1'))
            ->where('kd_keg2', '=', $this->getAttribute('kd_keg2'))
            ->where('kd_keg3', '=', $this->getAttribute('kd_keg3'))
            ->where('kd_keg4', '=', $this->getAttribute('kd_keg4'))
            ->where('kd_keg5', '=', $this->getAttribute('kd_keg5'));
    }
}
