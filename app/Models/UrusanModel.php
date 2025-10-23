<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UrusanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'REF_URUSAN'; // Hapus schema prefix
    protected $primaryKey = 'KD_URUSAN';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kd_urusan',
        'nm_urusan',
        'deleted_at',
    ];

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';
    const DELETED_AT = 'DELETED_AT'; // Tambahkan ini untuk SoftDeletes

    protected $casts = [
        'DELETED_AT' => 'datetime',
    ];

    protected $dates = [
        'CREATED_AT',
        'UPDATED_AT', 
        'DELETED_AT',
    ];

    // Override getTable untuk menambahkan schema
    public function getTable()
    {
        return 'REF_URUSAN';
    }

    public function getRouteKeyName()
    {
        return 'KD_URUSAN';
    }

    protected function asDateTime($value)
    {
        return parent::asDateTime($value);
    }

    public function getDeletedAtColumn()
    {
        return 'DELETED_AT';
    }
}