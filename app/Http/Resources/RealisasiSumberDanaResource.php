<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RealisasiSumberDanaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? $this->ID,
            'kd_ref1' => $this->kd_ref1 ?? $this->KD_REF1,
            'kd_ref2' => $this->kd_ref2 ?? $this->KD_REF2,
            'kd_ref3' => $this->kd_ref3 ?? $this->KD_REF3,
            'kd_ref4' => $this->kd_ref4 ?? $this->KD_REF4,
            'kd_ref5' => $this->kd_ref5 ?? $this->KD_REF5,
            'kd_ref6' => $this->kd_ref6 ?? $this->KD_REF6,
            'nm_sumber' => $this->nm_sumber ?? $this->NM_SUMBER,
            'tgl_diterima' => $this->tgl_diterima ?? $this->TGL_DITERIMA,
            'tahun' => $this->tahun ?? $this->TAHUN,
            'jumlah_sumber' => $this->jumlah_sumber ?? $this->JUMLAH_SUMBER,
            'keterangan' => $this->keterangan ?? $this->KETERANGAN,
            'keterangan_2' => $this->keterangan_2 ?? $this->KETERANGAN_2,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
