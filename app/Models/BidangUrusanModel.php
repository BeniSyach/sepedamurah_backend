<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BidangUrusanModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'PENGEMBALIAN.REF_BIDANG_URUSAN';
    public $incrementing = false;
    protected $primaryKey = null; // Composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_bu1',
        'kd_bu2',
        'nm_bu',
    ];

    // === Mapping kolom tanggal (Oracle case-sensitive) ===
    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';
    const DELETED_AT = 'DELETED_AT';

    protected $dates = ['DELETED_AT'];

    /**
     * Override query untuk update berdasarkan composite key.
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('KD_BU1', '=', $this->getAttribute('KD_BU1'))
            ->where('KD_BU2', '=', $this->getAttribute('KD_BU2'));
    }

    /**
     * Override delete agar soft delete bekerja di composite key.
     */
    protected function performDeleteOnModel()
    {
        // Soft delete (update kolom DELETED_AT)
        if ($this->usesSoftDeletes()) {
            $time = $this->freshTimestamp();

            static::where('KD_BU1', $this->KD_BU1)
                ->where('KD_BU2', $this->KD_BU2)
                ->update([static::DELETED_AT => $this->fromDateTime($time)]);

            $this->setAttribute(static::DELETED_AT, $time);
        } else {
            // Hard delete (hapus baris)
            static::where('KD_BU1', $this->KD_BU1)
                ->where('KD_BU2', $this->KD_BU2)
                ->delete();
        }
    }

    /**
     * Pastikan restore() juga bisa jalan kalau pakai SoftDeletes.
     */
    public function restore()
    {
        if ($this->usesSoftDeletes()) {
            static::where('KD_BU1', $this->KD_BU1)
                ->where('KD_BU2', $this->KD_BU2)
                ->update([static::DELETED_AT => null]);

            $this->setAttribute(static::DELETED_AT, null);
        }
    }
}
