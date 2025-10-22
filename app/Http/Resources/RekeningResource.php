<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RekeningResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_rekening1' => $this->KD_REKENING1 ?? $this->kd_rekening1,
            'kd_rekening2' => $this->KD_REKENING2 ?? $this->kd_rekening2,
            'kd_rekening3' => $this->KD_REKENING3 ?? $this->kd_rekening3,
            'kd_rekening4' => $this->KD_REKENING4 ?? $this->kd_rekening4,
            'kd_rekening5' => $this->KD_REKENING5 ?? $this->kd_rekening5,
            'kd_rekening6' => $this->KD_REKENING6 ?? $this->kd_rekening6,
            'nm_rekening'  => $this->NM_REKENING ?? $this->nm_rekening,
            'created_at'   => $this->CREATED_AT ?? $this->created_at,
            'updated_at'   => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
        ];
    }
}
