<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekRincianModel extends Model
{
    use HasFactory;

    protected $table = 'rek_rincian';
    protected $primaryKey = 'id';
    public $timestamps = false; // tabel tidak memiliki created_at & updated_at

    protected $fillable = [
        'kd_rincian1',
        'kd_rincian2',
        'kd_rincian3',
        'kd_rincian4',
        'kd_rincian5',
        'nm_rek_rincian',
    ];
}
