<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SP2DRekeningModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'sp2d_rekening';
    protected $primaryKey = 'id';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'sp2d_id',
        'kd_rekening1',
        'kd_rekening2',
        'kd_rekening3',
        'kd_rekening4',
        'kd_rekening5',
        'kd_rekening6',
        'nilai',
        'kd_keg1',
        'kd_keg2',
        'kd_keg3',
        'kd_keg4',
        'kd_keg5',
        'kd_subkeg1',
        'kd_subkeg2',
        'kd_subkeg3',
        'kd_subkeg4',
        'kd_subkeg5',
        'kd_subkeg6',
        'kd_prog1',
        'kd_prog2',
        'kd_prog3',
        'kd_urusan',
        'kd_bu1',
        'kd_bu2',
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

   // ==========================================================
    // 🔗 Relasi ke Rekening (composite key)
    // ==========================================================
    public function rekeningRef()
    {
        return RekeningModel::where('kd_rekening1', $this->kd_rekening1)
            ->where('kd_rekening2', $this->kd_rekening2)
            ->where('kd_rekening3', $this->kd_rekening3)
            ->where('kd_rekening4', $this->kd_rekening4)
            ->where('kd_rekening5', $this->kd_rekening5)
            ->where('kd_rekening6', $this->kd_rekening6)
            ->first();
    }

    // accessor alias
    public function getRekeningAttribute()
    {
        return $this->rekeningRef();
    }

    // ==========================================================
    // 🔗 Relasi ke Program
    // ==========================================================
    public function programRef()
    {
        return ProgramModel::where('kd_prog1', $this->kd_prog1)
            ->where('kd_prog2', $this->kd_prog2)
            ->where('kd_prog3', $this->kd_prog3)
            ->first();
    }

    public function getProgramAttribute()
    {
        return $this->programRef();
    }

    // ==========================================================
    // 🔗 Relasi ke Kegiatan
    // ==========================================================
    public function kegiatanRef()
    {
        return KegiatanModel::where('kd_keg1', $this->kd_keg1)
            ->where('kd_keg2', $this->kd_keg2)
            ->where('kd_keg3', $this->kd_keg3)
            ->where('kd_keg4', $this->kd_keg4)
            ->where('kd_keg5', $this->kd_keg5)
            ->first();
    }

    public function getKegiatanAttribute()
    {
        return $this->kegiatanRef();
    }

    // ==========================================================
    // 🔗 Relasi ke Sub Kegiatan
    // ==========================================================
    public function subkegiatanRef()
    {
        return SubKegiatanModel::where('kd_subkeg1', $this->kd_subkeg1)
            ->where('kd_subkeg2', $this->kd_subkeg2)
            ->where('kd_subkeg3', $this->kd_subkeg3)
            ->where('kd_subkeg4', $this->kd_subkeg4)
            ->where('kd_subkeg5', $this->kd_subkeg5)
            ->where('kd_subkeg6', $this->kd_subkeg6)
            ->first();
    }

    public function getSubkegiatanAttribute()
    {
        return $this->subkegiatanRef();
    }

    // ==========================================================
    // 🔗 Relasi ke Bidang Urusan
    // ==========================================================
    public function buRef()
    {
        return BidangUrusanModel::where('kd_bu1', $this->kd_bu1)
            ->where('kd_bu2', $this->kd_bu2)
            ->first();
    }

    public function getBuAttribute()
    {
        return $this->buRef();
    }
}
