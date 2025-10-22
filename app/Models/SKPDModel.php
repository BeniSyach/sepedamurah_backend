<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SKPDModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_opd';
    public $incrementing = false;
    protected $primaryKey = null; // karena composite key
    public $timestamps = true;

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'nm_opd',
        'status_penerimaan',
        'kode_opd',
        'hidden',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'status_penerimaan' => 'integer',
        'hidden' => 'integer',
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
     * Override query agar Eloquent tahu cara update berdasarkan composite key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_opd1', $this->getAttribute('kd_opd1'))
            ->where('kd_opd2', $this->getAttribute('kd_opd2'))
            ->where('kd_opd3', $this->getAttribute('kd_opd3'))
            ->where('kd_opd4', $this->getAttribute('kd_opd4'))
            ->where('kd_opd5', $this->getAttribute('kd_opd5'));
    }
    
}
