<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SumberDanaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_sumber_dana';
    public $incrementing = false;
    protected $primaryKey = null; // karena composite key
    public $timestamps = true;

    protected $fillable = [
        'kd_ref1',
        'kd_ref2',
        'kd_ref3',
        'kd_ref4',
        'kd_ref5',
        'kd_ref6',
        'nm_ref',
        'status',
        'jenis_sumber_dana',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Override agar Eloquent tahu cara update berdasarkan composite key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('kd_ref1', $this->getAttribute('kd_ref1'))
            ->where('kd_ref2', $this->getAttribute('kd_ref2'))
            ->where('kd_ref3', $this->getAttribute('kd_ref3'))
            ->where('kd_ref4', $this->getAttribute('kd_ref4'))
            ->where('kd_ref5', $this->getAttribute('kd_ref5'))
            ->where('kd_ref6', $this->getAttribute('kd_ref6'));
    }
}
