<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SP2DSumberDanaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'sp2d_sumber_dana';
    protected $primaryKey = 'id';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'sp2d_id',
        'kd_ref1',
        'kd_ref2',
        'kd_ref3',
        'kd_ref4',
        'kd_ref5',
        'kd_ref6',
        'nilai',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relasi ke Sp2dModel
     */
    public function sp2d()
    {
        return $this->belongsTo(Sp2dModel::class, 'sp2d_id', 'id_sp2d');
    }

    /**
     * Accessor untuk sumber dana (manual composite key)
     */
    public function getSumberDanaAttribute()
    {
        return SumberDanaModel::where('kd_ref1', $this->kd_ref1)
            ->where('kd_ref2', $this->kd_ref2)
            ->where('kd_ref3', $this->kd_ref3)
            ->where('kd_ref4', $this->kd_ref4)
            ->where('kd_ref5', $this->kd_ref5)
            ->where('kd_ref6', $this->kd_ref6)
            ->first();
    }
}
