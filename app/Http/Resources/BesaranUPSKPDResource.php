<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BesaranUPSKPDResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_opd1' => $this->kd_opd1 ?? $this->KD_OPD1,
            'kd_opd2' => $this->kd_opd2 ?? $this->KD_OPD2,
            'kd_opd3' => $this->kd_opd3 ?? $this->KD_OPD3,
            'kd_opd4' => $this->kd_opd4 ?? $this->KD_OPD4,
            'kd_opd5' => $this->kd_opd5 ?? $this->KD_OPD5,
            'tahun'   => $this->tahun ?? $this->TAHUN,
            'pagu'    => $this->pagu ?? $this->PAGU,
            'up_kkpd' => $this->up_kkpd ?? $this->UP_KKPD,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
            'skpd' => new SKPDResource($this->whenLoaded('skpd')),
        ];
    }
}
