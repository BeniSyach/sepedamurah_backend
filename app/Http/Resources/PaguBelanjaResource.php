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
            'kd_opd1' => $this->kd_opd1 ?? $this->KD_OPD1,
            'kd_opd2' => $this->kd_opd2 ?? $this->KD_OPD2,
            'kd_opd3' => $this->kd_opd3 ?? $this->KD_OPD3,
            'kd_opd4' => $this->kd_opd4 ?? $this->KD_OPD4,
            'kd_opd5' => $this->kd_opd5 ?? $this->KD_OPD5,
            'kd_opd6' => $this->kd_opd6 ?? $this->KD_OPD6,
            'kd_opd7' => $this->kd_opd7 ?? $this->KD_OPD7,
            'kd_opd8' => $this->kd_opd8 ?? $this->KD_OPD8,
            'nm_urusan'     => $this->nm_urusan,
            'kd_urusan' => $this->kd_urusan ?? $this->KD_URUSAN,
            'nm_bu'         => $this->nm_bu,
            'kd_bu1' => $this->kd_bu1 ?? $this->KD_BU1,
            'kd_bu2' => $this->kd_bu2 ?? $this->KD_BU2,
            'nm_program'    => $this->nm_program,
            'kd_prog1' => $this->kd_prog1 ?? $this->KD_PROG1,
            'kd_prog2' => $this->kd_prog2 ?? $this->KD_PROG2,
            'kd_prog3' => $this->kd_prog3 ?? $this->KD_PROG3,
            'nm_kegiatan'   => $this->nm_kegiatan,
            'kd_keg1' => $this->kd_keg1 ?? $this->KD_KEG1,
            'kd_keg2' => $this->kd_keg2 ?? $this->KD_KEG2,
            'kd_keg3' => $this->kd_keg3 ?? $this->KD_KEG3,
            'kd_keg4' => $this->kd_keg4 ?? $this->KD_KEG4,
            'kd_keg5' => $this->kd_keg5 ?? $this->KD_KEG5,
            'nm_subkegiatan'=> $this->nm_subkegiatan,
            'kd_subkeg1' => $this->kd_subkeg1 ?? $this->KD_SUBKEG1,
            'kd_subkeg2' => $this->kd_subkeg2 ?? $this->KD_SUBKEG2,
            'kd_subkeg3' => $this->kd_subkeg3 ?? $this->KD_SUBKEG3,
            'kd_subkeg4' => $this->kd_subkeg4 ?? $this->KD_SUBKEG4,
            'kd_subkeg5' => $this->kd_subkeg5 ?? $this->KD_SUBKEG5,
            'kd_subkeg6' => $this->kd_subkeg6 ?? $this->KD_SUBKEG6,
            'nm_rekening'   => $this->nm_rekening,
            'kd_rekening1' => $this->kd_rekening1 ?? $this->KD_REKENING1,
            'kd_rekening2' => $this->kd_rekening2 ?? $this->KD_REKENING2,
            'kd_rekening3' => $this->kd_rekening3 ?? $this->KD_REKENING3,
            'kd_rekening4' => $this->kd_rekening4 ?? $this->KD_REKENING4,
            'kd_rekening5' => $this->kd_rekening5 ?? $this->KD_REKENING5,
            'kd_rekening6' => $this->kd_rekening6 ?? $this->KD_REKENING6,

            'created_at'    => $this->created_at,
        ];
    }
}
