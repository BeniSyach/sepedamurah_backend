<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaguSumberDanaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_ref1'      => $this->kd_ref1 ?? $this->KD_REF1,
            'kd_ref2'      => $this->kd_ref2 ?? $this->KD_REF2,
            'kd_ref3'      => $this->kd_ref3 ?? $this->KD_REF3,
            'kd_ref4'      => $this->kd_ref4 ?? $this->KD_REF4,
            'kd_ref5'      => $this->kd_ref5 ?? $this->KD_REF5,
            'kd_ref6'      => $this->kd_ref6 ?? $this->KD_REF6,
            'tahun'        => $this->tahun ?? $this->TAHUN,
            'tgl_rekam'    => $this->tgl_rekam ?? $this->TGL_REKAM,
            'pagu'         => $this->pagu ?? $this->PAGU,
            'jumlah_silpa' => $this->jumlah_silpa ?? $this->JUMLAH_SILPA,
            'created_at'   => $this->created_at ?? $this->CREATED_AT,
            'updated_at'   => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at'   => $this->deleted_at ?? $this->DELETED_AT,
            'sumber_dana' => new SumberDanaResource($this->sumber_dana),
        ];
    }
}
