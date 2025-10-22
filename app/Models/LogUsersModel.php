<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogUsersModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'user_delete_log';
    protected $primaryKey = 'log_id';
    public $incrementing = false; // Karena Oracle pakai trigger + sequence
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'users_id',
        'deleted_time',
        'deleted_by',
        'alasan',
    ];

    // Mapping kolom Laravel
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    protected $dates = [
        'deleted_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relasi ke tabel users (jika ada model User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
}
