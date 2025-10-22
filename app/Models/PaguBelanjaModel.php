<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaguBelanjaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'pagu_belanja';
    protected $primaryKey = 'id_pb';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'tahun_rek',
        'kd_urusan',
        'kd_prog1', 'kd_prog2', 'kd_prog3',
        'kd_keg1', 'kd_keg2', 'kd_keg3', 'kd_keg4', 'kd_keg5',
        'kd_subkeg1', 'kd_subkeg2', 'kd_subkeg3', 'kd_subkeg4', 'kd_subkeg5', 'kd_subkeg6',
        'kd_rekening1', 'kd_rekening2', 'kd_rekening3', 'kd_rekening4', 'kd_rekening5', 'kd_rekening6',
        'jumlah_pagu',
        'kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5', 'kd_opd6', 'kd_opd7', 'kd_opd8',
        'kd_bu1', 'kd_bu2',
        'kd_relasi',
        'kd_berapax',
        'is_deleted',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at'; // meski pakai kolom IS_DELETED, ini untuk internal Laravel

    /**
     * Override SoftDeletes: pakai kolom IS_DELETED Oracle, bukan deleted_at
     */
    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where('is_deleted', 0);
        });

        static::deleting(function ($model) {
            $model->is_deleted = 1;
            $model->save();
        });
    }

    /**
     * Relasi contoh (opsional)
     * Relasi ke RefUrusan
     */
    public function urusan()
    {
        return $this->belongsTo(UrusanModel::class, 'kd_urusan', 'kd_urusan');
    }

   // ============================
    // Program (composite key)
    // ============================

    public function getProgramAttribute()
    {
        return ProgramModel::where('kd_prog1', $this->kd_prog1)
            ->where('kd_prog2', $this->kd_prog2)
            ->where('kd_prog3', $this->kd_prog3)
            ->first();
    }

    public function program()
    {
        return $this->hasOne(ProgramModel::class, null, null, null)
            ->where('kd_prog1', $this->kd_prog1)
            ->where('kd_prog2', $this->kd_prog2)
            ->where('kd_prog3', $this->kd_prog3);
    }

    // ============================
    // Kegiatan (composite key)
    // ============================

    public function getKegiatanAttribute()
    {
        return KegiatanModel::where('kd_keg1', $this->kd_keg1)
            ->where('kd_keg2', $this->kd_keg2)
            ->where('kd_keg3', $this->kd_keg3)
            ->where('kd_keg4', $this->kd_keg4)
            ->where('kd_keg5', $this->kd_keg5)
            ->first();
    }

    public function kegiatan()
    {
        return $this->hasOne(KegiatanModel::class, null, null, null)
            ->where('kd_keg1', $this->kd_keg1)
            ->where('kd_keg2', $this->kd_keg2)
            ->where('kd_keg3', $this->kd_keg3)
            ->where('kd_keg4', $this->kd_keg4)
            ->where('kd_keg5', $this->kd_keg5);
    }

    // ============================
    // SubKegiatan (composite key)
    // ============================
    public function getSubKegiatanAttribute()
    {
        return SubKegiatanModel::where('kd_subkeg1', $this->kd_subkeg1)
            ->where('kd_subkeg2', $this->kd_subkeg2)
            ->where('kd_subkeg3', $this->kd_subkeg3)
            ->where('kd_subkeg4', $this->kd_subkeg4)
            ->where('kd_subkeg5', $this->kd_subkeg5)
            ->where('kd_subkeg6', $this->kd_subkeg6)
            ->first();
    }

    public function subkegiatan()
    {
        return $this->hasOne(SubKegiatanModel::class, null, null, null)
            ->where('kd_subkeg1', $this->kd_subkeg1)
            ->where('kd_subkeg2', $this->kd_subkeg2)
            ->where('kd_subkeg3', $this->kd_subkeg3)
            ->where('kd_subkeg4', $this->kd_subkeg4)
            ->where('kd_subkeg5', $this->kd_subkeg5)
            ->where('kd_subkeg6', $this->kd_subkeg6);
    }

    // ============================
    // Rekening (composite key)
    // ============================
    public function getRekeningAttribute()
    {
        return RekeningModel::where('kd_rekening1', $this->kd_rekening1)
            ->where('kd_rekening2', $this->kd_rekening2)
            ->where('kd_rekening3', $this->kd_rekening3)
            ->where('kd_rekening4', $this->kd_rekening4)
            ->where('kd_rekening5', $this->kd_rekening5)
            ->where('kd_rekening6', $this->kd_rekening6)
            ->first();
    }

    public function rekening()
    {
        return $this->hasOne(RekeningModel::class, null, null, null)
            ->where('kd_rekening1', $this->kd_rekening1)
            ->where('kd_rekening2', $this->kd_rekening2)
            ->where('kd_rekening3', $this->kd_rekening3)
            ->where('kd_rekening4', $this->kd_rekening4)
            ->where('kd_rekening5', $this->kd_rekening5)
            ->where('kd_rekening6', $this->kd_rekening6);
    }

    // ============================
    // Bidang Urusan (composite key)
    // ============================
    public function getBuAttribute()
    {
        return BidangUrusanModel::where('kd_bu1', $this->kd_bu1)
            ->where('kd_bu2', $this->kd_bu2)
            ->first();
    }

    public function bu()
    {
        return $this->hasOne(BidangUrusanModel::class, null, null, null)
            ->where('kd_bu1', $this->kd_bu1)
            ->where('kd_bu2', $this->kd_bu2);
    }

    // ============================
    // SKPD (menggunakan kode_opd gabungan 8 segmen)
    // ============================
    public function getSkpdAttribute()
    {
        // Gabungkan kd_opd1..kd_opd8 menjadi string
        $kodeOpd = implode('.', array_map('trim', [
            $this->kd_opd1,
            $this->kd_opd2,
            $this->kd_opd3,
            $this->kd_opd4,
            $this->kd_opd5,
            $this->kd_opd6,
            $this->kd_opd7,
            $this->kd_opd8
        ]));

        return SKPDModel::where('kode_opd', $kodeOpd)->first();
    }

    public function skpd()
    {
        $kodeOpd = implode('.', array_map('trim', [
            $this->kd_opd1,
            $this->kd_opd2,
            $this->kd_opd3,
            $this->kd_opd4,
            $this->kd_opd5,
            $this->kd_opd6,
            $this->kd_opd7,
            $this->kd_opd8
        ]));

        return $this->hasOne(SKPDModel::class, 'kode_opd', 'kode_opd')->where('kode_opd', $kodeOpd);
    }
}
