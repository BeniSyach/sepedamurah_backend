<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_program';
    public $incrementing = false;
    protected $primaryKey = null; // karena pakai composite key
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kd_prog1',
        'kd_prog2',
        'kd_prog3',
        'nm_program',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * Override default save query to support composite primary key
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('kd_prog1', '=', $this->getAttribute('kd_prog1'))
            ->where('kd_prog2', '=', $this->getAttribute('kd_prog2'))
            ->where('kd_prog3', '=', $this->getAttribute('kd_prog3'));
    }

        // Relasi ke Program (composite key)
    public function program()
    {
        return $this->belongsTo(ProgramModel::class, 'kd_prog1', 'kd_prog1')
            ->whereColumn('pagu_belanja.kd_prog2', 'ref_program.kd_prog2')
            ->whereColumn('pagu_belanja.kd_prog3', 'ref_program.kd_prog3');
    }
}
