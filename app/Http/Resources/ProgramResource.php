<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kd_prog1'   => $this->KD_PROG1 ?? $this->kd_prog1,
            'kd_prog2'   => $this->KD_PROG2 ?? $this->kd_prog2,
            'kd_prog3'   => $this->KD_PROG3 ?? $this->kd_prog3,
            'nm_program' => $this->NM_PROGRAM ?? $this->nm_program,
            'created_at' => $this->CREATED_AT ?? $this->created_at,
            'updated_at' => $this->UPDATED_AT ?? $this->updated_at,
           'deleted_at' => $this->deleted_at ?? $this->DELETED_AT,
        ];
    }
}
