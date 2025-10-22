<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrusanResource extends JsonResource
{
    /**
     * Transformasi data jadi JSON untuk response API.
     */
    public function toArray(Request $request): array
    {
        return [
            'kd_urusan' => $this->KD_URUSAN ?? $this->kd_urusan,
            'nm_urusan' => $this->NM_URUSAN ?? $this->nm_urusan,
            'created_at' => $this->CREATED_AT ?? $this->created_at,
            'updated_at' => $this->UPDATED_AT ?? $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
        ];
    }
}
