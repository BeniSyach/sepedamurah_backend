<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisBelanjaModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'ref_jenis_belanja';
    public $incrementing = false;
    protected $primaryKey = null; // Karena composite key
    public $timestamps = true;

    protected $fillable = [
        'kd_ref1',
        'kd_ref2',
        'kd_ref3',
        'nm_belanja',
        'created_at',
        'updated_at',
        'deleted_at',
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
     * Override primary key query karena composite key
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('kd_ref1', '=', $this->getAttribute('kd_ref1'))
              ->where('kd_ref2', '=', $this->getAttribute('kd_ref2'))
              ->where('kd_ref3', '=', $this->getAttribute('kd_ref3'));

        return $query;
    }
}
