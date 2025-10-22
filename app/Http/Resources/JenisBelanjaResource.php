<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JenisBelanjaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_ref1' => $this->kd_ref1 ?? $this->KD_REF1,
            'kd_ref2' => $this->kd_ref2 ?? $this->KD_REF2,
            'kd_ref3' => $this->kd_ref3 ?? $this->KD_REF3,
            'nm_belanja' => $this->nm_belanja ?? $this->NM_BELANJA,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
