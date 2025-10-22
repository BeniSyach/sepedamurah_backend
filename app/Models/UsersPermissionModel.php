<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersPermissionModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'USERS_PERMISSIONS';
    protected $primaryKey = 'ID';
    public $incrementing = false; // Karena ID di-generate oleh trigger (sequence Oracle)
    protected $keyType = 'int';
    public $timestamps = true;

    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';
    const DELETED_AT = 'DELETED_AT';

    protected $fillable = [
        'ID',
        'USERS_ID',
        'USERS_RULE_ID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    protected $dates = [
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (opsional, tergantung struktur database)
    |--------------------------------------------------------------------------
    */

    // Relasi ke tabel USERS (jika ada model Users)
    public function user()
    {
        return $this->belongsTo(User::class, 'USERS_ID', 'ID');
    }

    // Relasi ke tabel RULES (jika ada model UsersRule)
    public function rule()
    {
        return $this->belongsTo(UsersRoleModel::class, 'USERS_RULE_ID', 'ID');
    }
}
