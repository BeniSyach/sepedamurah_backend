<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatasWaktuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'hari' => $this->hari,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'waktu_awal' => $this->waktu_awal,
            'waktu_akhir' => $this->waktu_akhir,
            'istirahat_awal' => $this->istirahat_awal,
            'istirahat_akhir' => $this->istirahat_akhir,
            'keterangan' => $this->keterangan,
            'skpd' => $this->all_opd 
                ? ['nm_opd' => 'Seluruh SKPD']
                : new SKPDResource($this->whenLoaded('skpd')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
    
}
