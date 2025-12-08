<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefDpaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nm_dpa' => $this->nm_dpa,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
