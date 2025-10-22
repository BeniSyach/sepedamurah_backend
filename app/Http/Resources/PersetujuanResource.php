<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersetujuanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? $this->ID,
            'konten' => $this->konten ?? $this->KONTEN,
            'created_at' => $this->created_at ?? $this->CREATED_AT,
            'updated_at' => $this->updated_at ?? $this->UPDATED_AT,
            'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
