<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekJenisModel extends Model
{
    protected $table = 'rek_jenis'; // nama tabel
    protected $primaryKey = 'id';
    public $timestamps = false; // karena tabel tidak ada created_at / updated_at

    protected $fillable = [
        'kd_jenis1',
        'kd_jenis2',
        'kd_jenis3',
        'nm_rek_jenis',
    ];
}
