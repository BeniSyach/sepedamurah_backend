<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekeningModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_rekening';
    public $incrementing = false;
    protected $primaryKey = null; // karena pakai composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_rekening1',
        'kd_rekening2',
        'kd_rekening3',
        'kd_rekening4',
        'kd_rekening5',
        'kd_rekening6',
        'nm_rekening',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Override default save query untuk mendukung composite primary key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_rekening1', '=', $this->getAttribute('kd_rekening1'))
            ->where('kd_rekening2', '=', $this->getAttribute('kd_rekening2'))
            ->where('kd_rekening3', '=', $this->getAttribute('kd_rekening3'))
            ->where('kd_rekening4', '=', $this->getAttribute('kd_rekening4'))
            ->where('kd_rekening5', '=', $this->getAttribute('kd_rekening5'))
            ->where('kd_rekening6', '=', $this->getAttribute('kd_rekening6'));
    }
}
