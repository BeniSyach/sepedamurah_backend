<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KategoriSPMResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? $this->ID,
            'kategori' => $this->kategori ?? $this->KATEGORI,
            'status' => $this->status ?? $this->STATUS,
            'date_created' => $this->date_created ?? $this->DATE_CREATED,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
