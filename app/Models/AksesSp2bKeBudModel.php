<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesSp2bKeBudModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'akses_sp2b_ke_bud';
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
        'ref_sp2b_ke_bud_id',
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
     * Relasi ke REF_SP2B_KE_BUD
     */
    public function refSp2bKeBud()
    {
        return $this->belongsTo(
            RefSp2bKeBudModel::class,
            'ref_sp2b_ke_bud_id',
            'id'
        );
    }
}
