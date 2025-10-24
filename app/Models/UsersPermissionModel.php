<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersPermissionModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'users_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena id di-generate oleh trigger (sequence oracle)
    protected $keyType = 'int';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'id',
        'users_id',
        'users_rule_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (opsional, tergantung struktur database)
    |--------------------------------------------------------------------------
    */

    // relasi ke tabel users (jika ada model User)
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }

    // relasi ke tabel rules (jika ada model UsersRoleModel)
    public function rule()
    {
        return $this->belongsTo(UsersRoleModel::class, 'users_rule_id', 'id');
    }
}
