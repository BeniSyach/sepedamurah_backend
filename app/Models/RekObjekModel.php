<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekObjekModel extends Model
{
    use HasFactory;

    protected $table = 'rek_objek';
    protected $primaryKey = 'id';
    public $timestamps = false; // karena tabel tidak ada created_at/updated_at

    protected $fillable = [
        'kd_objek1',
        'kd_objek2',
        'kd_objek3',
        'kd_objek4',
        'nm_rek_objek',
    ];
}
