<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     protected $connection = 'oracle';
     protected $table = 'users';
     protected $primaryKey = 'id';
     public $timestamps = true;

     const CREATED_AT = 'date_created';
     const UPDATED_AT = 'updated_at'; // Oracle tidak punya kolom updated_at

     protected $dates = ['date_created', 'deleted_at'];

     protected $fillable = [
        'id',
        'nik',
        'nip',
        'name',
        'email',
        'no_hp',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'image',
        'password',
        'is_active',
        'date_created',
        'visualisasi_tte',
        'deleted',
        'chat_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

     // Soft delete override karena kolom Oracle kamu pakai angka (DELETED)
    protected static function booted()
    {
        static::deleting(function ($model) {
            $model->deleted = 1;
            $model->save();
        });

        static::restoring(function ($model) {
            $model->deleted = 0;
            $model->save();
        });
    }

    // Supaya query default otomatis skip user yang deleted = 1
    protected static function bootSoftDeletes()
    {
        static::addGlobalScope('not_deleted', function ($builder) {
            $builder->where('deleted', 0);
        });
    }

    // Ganti method Laravel yang cari password
    public function getAuthPassword()
    {
        return $this->password;
    }

     // JWT
     public function getJWTIdentifier()
     {
        return $this->id;
     }
 
     public function getJWTCustomClaims()
     {
         return [];
     }

     public function permissions()
    {
        return $this->hasMany(UsersPermissionModel::class, 'users_id', 'id');
    }

    public function rules()
    {
        return $this->belongsToMany(
            UsersRoleModel::class,            // model tujuan
            UsersPermissionModel::class,      // tabel pivot
            'users_id',                       // foreign key di tabel pivot untuk user
            'users_rule_id',                  // foreign key di tabel pivot untuk rule
            'id',                             // primary key di tabel users
            'id'                              // primary key di tabel rules
        );
    }

    public function getSkpdAttribute()
    {
        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
