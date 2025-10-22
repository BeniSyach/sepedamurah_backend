<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LogTTEResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'id_berkas' => $this->id_berkas,
            'kategori' => $this->kategori,
            'tte' => $this->tte,
            'status' => $this->status,
            'tgl_tte' => $this->tgl_tte,
            'keterangan' => $this->keterangan,
            'message' => $this->message,
            'id_penandatangan' => $this->id_penandatangan,
            'nama_penandatangan' => $this->nama_penandatangan,
            'date_created' => $this->date_created,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
        ];
    }
}
