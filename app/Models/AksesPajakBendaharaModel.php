<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesPajakBendaharaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'akses_pajak_bendahara';
    protected $primaryKey = 'id';

    // ID diisi oleh sequence + trigger Oracle
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'ref_pajak_id',
        'tahun',
    ];

    // === Mapping kolom timestamp (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = ['deleted_at'];

    /**
     * Relasi ke tabel ref_pajak_bendahara
     */
    public function refPajakBendahara()
    {
        return $this->belongsTo(
            RefPajakBendaharaModel::class,
            'ref_pajak_id',
            'id'
        );
    }
}
