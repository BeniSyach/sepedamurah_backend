<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->ID ?? $this->id,
            'nik' => $this->NIK ?? $this->nik,
            'nip' => $this->NIP ?? $this->nip,
            'name' => $this->NAME ?? $this->name,
            'email' => $this->EMAIL ?? $this->email,
            'no_hp' => $this->NO_HP ?? $this->no_hp,
            'kd_opd1' => $this->KD_OPD1 ?? $this->kd_opd1,
            'kd_opd2' => $this->KD_OPD2 ?? $this->kd_opd2,
            'kd_opd3' => $this->KD_OPD3 ?? $this->kd_opd3,
            'kd_opd4' => $this->KD_OPD4 ?? $this->kd_opd4,
            'kd_opd5' => $this->KD_OPD5 ?? $this->kd_opd5,
            'image' => $this->IMAGE ?? $this->image,
            'is_active' => $this->IS_ACTIVE ?? $this->is_active,
            'visualisasi_tte' => $this->VISUALISASI_TTE ?? $this->visualisasi_tte,
            'chat_id' => $this->CHAT_ID ?? $this->chat_id,
            'date_created' => $this->DATE_CREATED ?? $this->date_created,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
        ];
    }
}
