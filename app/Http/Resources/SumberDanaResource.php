<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SumberDanaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_ref1' => $this->kd_ref1,
            'kd_ref2' => $this->kd_ref2,
            'kd_ref3' => $this->kd_ref3,
            'kd_ref4' => $this->kd_ref4,
            'kd_ref5' => $this->kd_ref5,
            'kd_ref6' => $this->kd_ref6,
            'nm_ref' => $this->nm_ref,
            'status' => $this->status ,
            'jenis_sumber_dana' => $this->jenis_sumber_dana ,
            'created_at' => $this->created_at ,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
