<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Sp2dRekeningResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'ID' => $this->ID,
            'SP2D_ID' => $this->SP2D_ID,
            'KD_REKENING1' => $this->KD_REKENING1,
            'KD_REKENING2' => $this->KD_REKENING2,
            'KD_REKENING3' => $this->KD_REKENING3,
            'KD_REKENING4' => $this->KD_REKENING4,
            'KD_REKENING5' => $this->KD_REKENING5,
            'KD_REKENING6' => $this->KD_REKENING6,
            'NILAI' => $this->NILAI,
            'KD_KEG1' => $this->KD_KEG1,
            'KD_KEG2' => $this->KD_KEG2,
            'KD_KEG3' => $this->KD_KEG3,
            'KD_KEG4' => $this->KD_KEG4,
            'KD_KEG5' => $this->KD_KEG5,
            'KD_SUBKEG1' => $this->KD_SUBKEG1,
            'KD_SUBKEG2' => $this->KD_SUBKEG2,
            'KD_SUBKEG3' => $this->KD_SUBKEG3,
            'KD_SUBKEG4' => $this->KD_SUBKEG4,
            'KD_SUBKEG5' => $this->KD_SUBKEG5,
            'KD_SUBKEG6' => $this->KD_SUBKEG6,
            'KD_PROG1' => $this->KD_PROG1,
            'KD_PROG2' => $this->KD_PROG2,
            'KD_PROG3' => $this->KD_PROG3,
            'KD_URUSAN' => $this->KD_URUSAN,
            'KD_BU1' => $this->KD_BU1,
            'KD_BU2' => $this->KD_BU2,
            'CREATED_AT' => $this->CREATED_AT,
            'UPDATED_AT' => $this->UPDATED_AT,
            'DELETED_AT' => $this->DELETED_AT,
        ];
    }
}
