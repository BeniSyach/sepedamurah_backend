<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersRoleModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'users_role';
    protected $primaryKey = 'id';
    public $incrementing = false; // Karena ID diisi oleh trigger USR_RULE (sequence no_role)
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'rule',
    ];

    // Mapping kolom timestamp Oracle
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

        // Relasi ke menu
    public function menus()
    {
        return $this->hasMany(UsersRoleMenuModel::class, 'role_id', 'id');
    }
}
