<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SP2DSumberDanaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'ID' => $this->ID,
            'SP2D_ID' => $this->SP2D_ID,
            'KD_REF1' => $this->KD_REF1,
            'KD_REF2' => $this->KD_REF2,
            'KD_REF3' => $this->KD_REF3,
            'KD_REF4' => $this->KD_REF4,
            'KD_REF5' => $this->KD_REF5,
            'KD_REF6' => $this->KD_REF6,
            'NILAI' => $this->NILAI,
            'CREATED_AT' => $this->CREATED_AT,
            'UPDATED_AT' => $this->UPDATED_AT,
            'DELETED_AT' => $this->DELETED_AT,
        ];
    }
}
