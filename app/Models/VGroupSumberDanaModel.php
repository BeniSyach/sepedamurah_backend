<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VGroupSumberDanaModel extends Model
{
    protected $connection = 'oracle';
    protected $table = 'v_group_sumber_dana';
    public $incrementing = false;
    public $timestamps = false;
}
