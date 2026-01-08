<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AksesKuasaBUDResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_kbud' => $this->id_kbud,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'date_created' => $this->date_created,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'skpd' => new SKPDResource($this->whenLoaded('skpd')),
        ];
    }
}
