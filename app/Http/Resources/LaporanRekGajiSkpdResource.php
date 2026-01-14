<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanRekGajiSkpdResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'kd_opd1'       => $this->kd_opd1,
            'kd_opd2'       => $this->kd_opd2,
            'kd_opd3'       => $this->kd_opd3,
            'kd_opd4'       => $this->kd_opd4,
            'kd_opd5'       => $this->kd_opd5,

            'tahun'         => $this->tahun,

            // identitas rekonsiliasi gaji
            'rek_gaji_id'   => $this->rek_gaji_id,
            'nama_rek_gaji' => $this->rekGaji->nm_rekonsiliasi_gaji_skpd ?? null,

            // status laporan
            'diterima'          => $this->diterima,
            'ditolak'           => $this->ditolak,
            'alasan_tolak'      => $this->alasan_tolak,
            'proses'            => $this->proses,
            'supervisor_proses' => $this->supervisor_proses,
            'file'              => $this->file,

            // Accessor SKPD
            'skpd' => new SKPDResource($this->skpd),

            // Relasi
            'RekonsiliasiGajiSKPD' => new RefRekonsiliasiGajiSkpdResource(
                $this->whenLoaded('rekGaji')
            ),
            'user'     => new UserResource($this->whenLoaded('user')),
            'operator' => new AksesOperatorResource($this->whenLoaded('operator')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
