<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanPajakBendaharaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'laporan_pajak_bendahara';
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
        'user_id',
        'id_operator',
        'nama_operator',
        'tahun',
        'proses',
        'supervisor_proses',
        'file',
        'diterima',
        'ditolak',
        'alasan_tolak',
        'created_at'
    ];

    // === Mapping kolom timestamp (huruf kecil) ===
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'diterima',
        'ditolak',
        'deleted_at',
    ];

    /**
     * Relasi ke REF_PAJAK_BENDAHARA
     */
    public function refPajakBendahara()
    {
        return $this->belongsTo(
            RefPajakBendaharaModel::class,
            'ref_pajak_id',
            'id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'id_operator', 'id');
    }

    public function getSkpdAttribute()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
