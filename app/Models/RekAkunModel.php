<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekAkunModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'rek_akun'; // nama tabel tanpa schema
    protected $primaryKey = 'id';
    public $timestamps = false; // karena tabel tidak ada created_at / updated_at

    protected $fillable = [
        'kode',
        'nm_rek_akun',
    ];
}
