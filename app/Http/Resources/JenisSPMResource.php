<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JenisSPMResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'kategori' => $this->kategori ?? $this->KATEGORI,
            'nama_berkas' => $this->nama_berkas ?? $this->NAMA_BERKAS,
            'status_penerimaan' => $this->status_penerimaan ?? $this->STATUS_PENERIMAAN,
            'date_created' => $this->date_created ?? $this->DATE_CREATED,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
