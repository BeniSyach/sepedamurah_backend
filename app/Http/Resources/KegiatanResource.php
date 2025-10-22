<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KegiatanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_keg1'     => $this->KD_KEG1 ?? $this->kd_keg1,
            'kd_keg2'     => $this->KD_KEG2 ?? $this->kd_keg2,
            'kd_keg3'     => $this->KD_KEG3 ?? $this->kd_keg3,
            'kd_keg4'     => $this->KD_KEG4 ?? $this->kd_keg4,
            'kd_keg5'     => $this->KD_KEG5 ?? $this->kd_keg5,
            'nm_kegiatan' => $this->NM_KEGIATAN ?? $this->nm_kegiatan,
            'created_at'  => $this->CREATED_AT ?? $this->created_at,
            'updated_at'  => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
