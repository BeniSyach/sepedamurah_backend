<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RekeningResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_rekening1' => $this->kd_rekening1,
            'kd_rekening2' => $this->kd_rekening2,
            'kd_rekening3' => $this->kd_rekening3 ,
            'kd_rekening4' => $this->kd_rekening4 ,
            'kd_rekening5' => $this->kd_rekening5 ,
            'kd_rekening6' => $this->kd_rekening6 ,
            'nm_rekening'  => $this->nm_rekening,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'deleted_at'   => $this->deleted_at,
        ];
    }
}
