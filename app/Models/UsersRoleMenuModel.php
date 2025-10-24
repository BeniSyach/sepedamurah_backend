<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersRoleMenuModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'users_role_menu';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai sequence
    public $timestamps = false; // tidak ada created_at / updated_at

    protected $fillable = [
        'role_id',
        'menu',
    ];

    // Relasi balik ke role
    public function role()
    {
        return $this->belongsTo(UsersRoleModel::class, 'role_id', 'id');
    }
}
