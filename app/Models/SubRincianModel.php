<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubRincianModel extends Model
{
    use HasFactory;

    protected $table = 'sub_rincian';
    protected $primaryKey = 'id';
    public $timestamps = false; // karena tidak ada created_at & updated_at

    protected $fillable = [
        'kd_subrincian1',
        'kd_subrincian2',
        'kd_subrincian3',
        'kd_subrincian4',
        'kd_subrincian5',
        'kd_subrincian6',
        'nm_sub_rincian',
    ];
}
