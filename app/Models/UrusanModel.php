<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UrusanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_urusan'; // hapus schema prefix
    protected $primaryKey = 'kd_urusan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kd_urusan',
        'nm_urusan',
        'deleted_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at'; // tambahkan ini untuk SoftDeletes

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at', 
        'deleted_at',
    ];

    // override getTable untuk menambahkan schema
    public function getTable()
    {
        return 'ref_urusan';
    }

    public function getRouteKeyName()
    {
        return 'kd_urusan';
    }

    protected function asDateTime($value)
    {
        return parent::asDateTime($value);
    }

    public function getDeletedAtColumn()
    {
        return 'deleted_at';
    }
}
