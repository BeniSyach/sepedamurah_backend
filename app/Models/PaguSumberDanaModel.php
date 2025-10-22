<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaguSumberDanaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'pagu_sumber_dana';
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
        'tgl_rekam',
        'tahun',
        'pagu',
        'jumlah_silpa',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'pagu' => 'float',
        'jumlah_silpa' => 'float',
        'tgl_rekam' => 'datetime',
    ];

    protected $dates = [
        'tgl_rekam',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

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

        /**
     * Relasi ke tabel ref_sumber_dana (manual composite key)
     */
    public function sumberDana()
    {
        return $this->hasOne(SumberDanaModel::class, null, null, null)
            ->where('kd_ref1', $this->kd_ref1)
            ->where('kd_ref2', $this->kd_ref2)
            ->where('kd_ref3', $this->kd_ref3)
            ->where('kd_ref4', $this->kd_ref4)
            ->where('kd_ref5', $this->kd_ref5)
            ->where('kd_ref6', $this->kd_ref6);
    }

    /**
     * Override agar update/delete berdasarkan composite key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_ref1', $this->getAttribute('kd_ref1'))
            ->where('kd_ref2', $this->getAttribute('kd_ref2'))
            ->where('kd_ref3', $this->getAttribute('kd_ref3'))
            ->where('kd_ref4', $this->getAttribute('kd_ref4'))
            ->where('kd_ref5', $this->getAttribute('kd_ref5'))
            ->where('kd_ref6', $this->getAttribute('kd_ref6'))
            ->where('tahun', $this->getAttribute('tahun'));
    }
}
