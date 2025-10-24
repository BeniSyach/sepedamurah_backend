<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BidangUrusanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_bidang_urusan';
    public $incrementing = false;
    protected $primaryKey = null; // composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_bu1',
        'kd_bu2',
        'nm_bu',
    ];

    // === Mapping kolom tanggal (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];

    /**
     * Override query untuk update berdasarkan composite key.
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_bu1', '=', $this->getAttribute('kd_bu1'))
            ->where('kd_bu2', '=', $this->getAttribute('kd_bu2'));
    }

    /**
     * Override delete agar soft delete bekerja di composite key.
     */
    protected function performDeleteOnModel()
    {
        // Soft delete (update kolom deleted_at)
        if ($this->usesSoftDeletes()) {
            $time = $this->freshTimestamp();

            static::where('kd_bu1', $this->kd_bu1)
                ->where('kd_bu2', $this->kd_bu2)
                ->update([static::DELETED_AT => $this->fromDateTime($time)]);

            $this->setAttribute(static::DELETED_AT, $time);
        } else {
            // Hard delete (hapus baris)
            static::where('kd_bu1', $this->kd_bu1)
                ->where('kd_bu2', $this->kd_bu2)
                ->delete();
        }
    }

    /**
     * Pastikan restore() juga bisa jalan kalau pakai SoftDeletes.
     */
    public function restore()
    {
        if ($this->usesSoftDeletes()) {
            static::where('kd_bu1', $this->kd_bu1)
                ->where('kd_bu2', $this->kd_bu2)
                ->update([static::DELETED_AT => null]);

            $this->setAttribute(static::DELETED_AT, null);
        }
    }
}
