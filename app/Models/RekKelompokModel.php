<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekKelompokModel extends Model
{
    protected $table = 'rek_kelompok'; // nama tabel
    protected $primaryKey = 'id';
    public $timestamps = false; // karena tabel tidak ada created_at / updated_at

    protected $fillable = [
        'kd_kel1',
        'kd_kel2',
        'nm_rek_kelompok',
    ];
}
