<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaguBelanjaResource extends JsonResource
{
    /**
     * Ambil properti dengan fallback, aman untuk stdClass atau model
     */
    private function attr($lower, $upper)
    {
        if (is_object($this)) {
            return $this->$lower ?? ($this->$upper ?? null);
        }
        return null;
    }

    public function toArray($request)
    {
        return [
            'id_pb'         => $this->id_pb ?? null,
            'tahun_rek'     => $this->tahun_rek ?? null,
            'kd_berapax'    => $this->kd_berapax ?? null,
            'jumlah_pagu'   => $this->jumlah_pagu ?? null,

            'nm_opd'        => $this->nm_opd ?? null,
            'kd_opd1'       => $this->attr('kd_opd1', 'KD_OPD1'),
            'kd_opd2'       => $this->attr('kd_opd2', 'KD_OPD2'),
            'kd_opd3'       => $this->attr('kd_opd3', 'KD_OPD3'),
            'kd_opd4'       => $this->attr('kd_opd4', 'KD_OPD4'),
            'kd_opd5'       => $this->attr('kd_opd5', 'KD_OPD5'),
            'kd_opd6'       => $this->attr('kd_opd6', 'KD_OPD6'),
            'kd_opd7'       => $this->attr('kd_opd7', 'KD_OPD7'),
            'kd_opd8'       => $this->attr('kd_opd8', 'KD_OPD8'),

            'nm_urusan'     => $this->nm_urusan ?? null,
            'kd_urusan'     => $this->attr('kd_urusan', 'KD_URUSAN'),

            'nm_bu'         => $this->nm_bu ?? null,
            'kd_bu1'        => $this->attr('kd_bu1', 'KD_BU1'),
            'kd_bu2'        => $this->attr('kd_bu2', 'KD_BU2'),

            'nm_program'    => $this->nm_program ?? null,
            'kd_prog1'      => $this->attr('kd_prog1', 'KD_PROG1'),
            'kd_prog2'      => $this->attr('kd_prog2', 'KD_PROG2'),
            'kd_prog3'      => $this->attr('kd_prog3', 'KD_PROG3'),

            'nm_kegiatan'   => $this->nm_kegiatan ?? null,
            'kd_keg1'       => $this->attr('kd_keg1', 'KD_KEG1'),
            'kd_keg2'       => $this->attr('kd_keg2', 'KD_KEG2'),
            'kd_keg3'       => $this->attr('kd_keg3', 'KD_KEG3'),
            'kd_keg4'       => $this->attr('kd_keg4', 'KD_KEG4'),
            'kd_keg5'       => $this->attr('kd_keg5', 'KD_KEG5'),

            'nm_subkegiatan'=> $this->nm_subkegiatan ?? null,
            'kd_subkeg1'    => $this->attr('kd_subkeg1', 'KD_SUBKEG1'),
            'kd_subkeg2'    => $this->attr('kd_subkeg2', 'KD_SUBKEG2'),
            'kd_subkeg3'    => $this->attr('kd_subkeg3', 'KD_SUBKEG3'),
            'kd_subkeg4'    => $this->attr('kd_subkeg4', 'KD_SUBKEG4'),
            'kd_subkeg5'    => $this->attr('kd_subkeg5', 'KD_SUBKEG5'),
            'kd_subkeg6'    => $this->attr('kd_subkeg6', 'KD_SUBKEG6'),

            'nm_rekening'   => $this->nm_rekening ?? null,
            'kd_rekening1'  => $this->attr('kd_rekening1', 'KD_REKENING1'),
            'kd_rekening2'  => $this->attr('kd_rekening2', 'KD_REKENING2'),
            'kd_rekening3'  => $this->attr('kd_rekening3', 'KD_REKENING3'),
            'kd_rekening4'  => $this->attr('kd_rekening4', 'KD_REKENING4'),
            'kd_rekening5'  => $this->attr('kd_rekening5', 'KD_REKENING5'),
            'kd_rekening6'  => $this->attr('kd_rekening6', 'KD_REKENING6'),

            'nm_sumber_dana' => $this->nm_sumber_dana ?? ($this->sumberDana->nm_ref ?? null),
            'kd_pagu_sd1'   => $this->attr('kd_pagu_sd1', 'KD_PAGU_SD1'),
            'kd_pagu_sd2'   => $this->attr('kd_pagu_sd2', 'KD_PAGU_SD2'),
            'kd_pagu_sd3'   => $this->attr('kd_pagu_sd3', 'KD_PAGU_SD3'),
            'kd_pagu_sd4'   => $this->attr('kd_pagu_sd4', 'KD_PAGU_SD4'),
            'kd_pagu_sd5'   => $this->attr('kd_pagu_sd5', 'KD_PAGU_SD5'),
            'kd_pagu_sd6'   => $this->attr('kd_pagu_sd6', 'KD_PAGU_SD6'),
            'kode_pagu_sd'  => implode('.', array_filter([
                $this->attr('kd_pagu_sd1', 'KD_PAGU_SD1'),
                $this->attr('kd_pagu_sd2', 'KD_PAGU_SD2'),
                $this->attr('kd_pagu_sd3', 'KD_PAGU_SD3'),
                $this->attr('kd_pagu_sd4', 'KD_PAGU_SD4'),
                $this->attr('kd_pagu_sd5', 'KD_PAGU_SD5'),
                $this->attr('kd_pagu_sd6', 'KD_PAGU_SD6'),
            ])),

            'kd_ref1'       => $this->attr('kd_ref1', 'KD_REF1') ?? ($this->sumberDana->kd_ref1 ?? null),
            'kd_ref2'       => $this->attr('kd_ref2', 'KD_REF2') ?? ($this->sumberDana->kd_ref2 ?? null),
            'kd_ref3'       => $this->attr('kd_ref3', 'KD_REF3') ?? ($this->sumberDana->kd_ref3 ?? null),
            'kd_ref4'       => $this->attr('kd_ref4', 'KD_REF4') ?? ($this->sumberDana->kd_ref4 ?? null),
            'kd_ref5'       => $this->attr('kd_ref5', 'KD_REF5') ?? ($this->sumberDana->kd_ref5 ?? null),
            'kd_ref6'       => $this->attr('kd_ref6', 'KD_REF6') ?? ($this->sumberDana->kd_ref6 ?? null),
            'kode_sumber_dana' => implode('.', array_filter([
                $this->attr('kd_ref1', 'KD_REF1') ?? ($this->sumberDana->kd_ref1 ?? null),
                $this->attr('kd_ref2', 'KD_REF2') ?? ($this->sumberDana->kd_ref2 ?? null),
                $this->attr('kd_ref3', 'KD_REF3') ?? ($this->sumberDana->kd_ref3 ?? null),
                $this->attr('kd_ref4', 'KD_REF4') ?? ($this->sumberDana->kd_ref4 ?? null),
                $this->attr('kd_ref5', 'KD_REF5') ?? ($this->sumberDana->kd_ref5 ?? null),
                $this->attr('kd_ref6', 'KD_REF6') ?? ($this->sumberDana->kd_ref6 ?? null),
            ])),

            'created_at'    => isset($this->created_at) ? $this->created_at : null,
        ];
    }
}