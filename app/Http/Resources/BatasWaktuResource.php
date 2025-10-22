<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatasWaktuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->ID ?? $this->id,
            'hari' => $this->HARI ?? $this->hari,
            'kd_opd1' => $this->KD_OPD1 ?? $this->kd_opd1,
            'kd_opd2' => $this->KD_OPD2 ?? $this->kd_opd2,
            'kd_opd3' => $this->KD_OPD3 ?? $this->kd_opd3,
            'kd_opd4' => $this->KD_OPD4 ?? $this->kd_opd4,
            'kd_opd5' => $this->KD_OPD5 ?? $this->kd_opd5,
            'waktu_awal' => $this->WAKTU_AWAL ?? $this->waktu_awal,
            'waktu_akhir' => $this->WAKTU_AKHIR ?? $this->waktu_akhir,
            'istirahat_awal' => $this->ISTIRAHAT_AWAL ?? $this->istirahat_awal,
            'istirahat_akhir' => $this->ISTIRAHAT_AKHIR ?? $this->istirahat_akhir,
            'keterangan' => $this->KETERANGAN ?? $this->keterangan,
            'created_at' => $this->CREATED_AT ?? $this->created_at,
            'updated_at' => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
            'skpd' => new SKPDResource($this->whenLoaded('skpd')),
        ];
    }
}
