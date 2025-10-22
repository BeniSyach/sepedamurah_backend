<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubKegiatanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_subkeg1'     => $this->KD_SUBKEG1 ?? $this->kd_subkeg1,
            'kd_subkeg2'     => $this->KD_SUBKEG2 ?? $this->kd_subkeg2,
            'kd_subkeg3'     => $this->KD_SUBKEG3 ?? $this->kd_subkeg3,
            'kd_subkeg4'     => $this->KD_SUBKEG4 ?? $this->kd_subkeg4,
            'kd_subkeg5'     => $this->KD_SUBKEG5 ?? $this->kd_subkeg5,
            'kd_subkeg6'     => $this->KD_SUBKEG6 ?? $this->kd_subkeg6,
            'nm_subkegiatan' => $this->NM_SUBKEGIATAN ?? $this->nm_subkegiatan,
            'created_at'     => $this->CREATED_AT ?? $this->created_at,
            'updated_at'     => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
