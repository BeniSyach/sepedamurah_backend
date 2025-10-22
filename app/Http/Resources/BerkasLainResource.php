<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BerkasLainResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tgl_surat' => $this->tgl_surat,
            'nama_file_asli' => $this->nama_file_asli,
            'nama_dokumen' => $this->nama_dokumen,
            'status_tte' => $this->status_tte,
            'file_sdh_tte' => $this->file_sdh_tte,
            'users_id' => $this->users_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'user' => $this->whenLoaded('user'),
        ];
    }
}
