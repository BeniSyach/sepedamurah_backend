<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BidangUrusanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_bu1' => $this->KD_BU1 ?? $this->kd_bu1,
            'kd_bu2' => $this->KD_BU2 ?? $this->kd_bu2,
            'nm_bu'  => $this->NM_BU ?? $this->nm_bu,
            'created_at' => $this->CREATED_AT ?? $this->created_at,
            'updated_at' => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
        ];
    }
}
