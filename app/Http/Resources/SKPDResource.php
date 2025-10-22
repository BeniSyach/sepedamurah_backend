<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SKPDResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_opd1' => $this->kd_opd1 ?? $this->KD_OPD1,
            'kd_opd2' => $this->kd_opd2 ?? $this->KD_OPD2,
            'kd_opd3' => $this->kd_opd3 ?? $this->KD_OPD3,
            'kd_opd4' => $this->kd_opd4 ?? $this->KD_OPD4,
            'kd_opd5' => $this->kd_opd5 ?? $this->KD_OPD5,
            'nm_opd' => $this->nm_opd ?? $this->NM_OPD,
            'status_penerimaan' => $this->status_penerimaan ?? $this->STATUS_PENERIMAAN,
            'kode_opd' => $this->kode_opd ?? $this->KODE_OPD,
            'hidden' => $this->hidden ?? $this->HIDDEN,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
