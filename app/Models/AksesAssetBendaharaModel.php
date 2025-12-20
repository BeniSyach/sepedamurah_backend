<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesAssetBendaharaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'akses_asset_bendahara';
    protected $primaryKey = 'id';

    // ID di-generate oleh sequence + trigger Oracle
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'ref_asset_id',
        'tahun',
    ];

    // === Mapping kolom timestamp (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Relasi ke REF_ASSET_BENDAHARA
     */
    public function refAssetBendahara()
    {
        return $this->belongsTo(
            RefAssetBendaharaModel::class,
            'ref_asset_id',
            'id'
        );
    }
}
