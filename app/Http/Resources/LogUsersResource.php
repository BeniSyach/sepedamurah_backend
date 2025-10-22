<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LogUsersResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'log_id' => $this->log_id,
            'users_id' => $this->users_id,
            'deleted_time' => $this->deleted_time,
            'deleted_by' => $this->deleted_by,
            'alasan' => $this->alasan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->DELETED_AT ?? $this->deleted_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
