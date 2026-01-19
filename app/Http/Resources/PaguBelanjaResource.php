<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaguBelanjaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_pb'         => $this->id_pb,
            'tahun_rek'     => $this->tahun_rek,
            'kd_berapax'    => $this->kd_berapax,
            'jumlah_pagu'   => $this->jumlah_pagu,

            'nm_opd'        => $this->nm_opd,
            'nm_urusan'     => $this->nm_urusan,
            'nm_bu'         => $this->nm_bu,
            'nm_program'    => $this->nm_program,
            'nm_kegiatan'   => $this->nm_kegiatan,
            'nm_subkegiatan'=> $this->nm_subkegiatan,
            'nm_rekening'   => $this->nm_rekening,

            'created_at'    => $this->created_at,
        ];
    }
}
