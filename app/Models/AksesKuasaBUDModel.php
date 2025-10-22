<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AksesKuasaBUDModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'KUASA_BUD';
    protected $primaryKey = 'id';
    public $incrementing = false; // ID diisi dari trigger Oracle
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_kbud',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'date_created',
    ];

    // Mapping kolom timestamp Laravel ke Oracle
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'date_created',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_kbud', 'id');
    }

    public function skpd()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
