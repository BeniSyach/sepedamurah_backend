<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PengembalianResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'no_sts' => $this->no_sts,
            'nik' => $this->nik,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'tahun' => $this->tahun,
            'kd_rek1' => $this->kd_rek1,
            'kd_rek2' => $this->kd_rek2,
            'kd_rek3' => $this->kd_rek3,
            'kd_rek4' => $this->kd_rek4,
            'kd_rek5' => $this->kd_rek5,
            'kd_rek6' => $this->kd_rek6,
            'nm_rekening' => $this->nm_rekening,
            'keterangan' => $this->keterangan,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'jml_pengembalian' => $this->jml_pengembalian,
            'tgl_rekam' => $this->tgl_rekam,
            'jml_yang_disetor' => $this->jml_yang_disetor,
            'tgl_setor' => $this->tgl_setor,
            'nip_perekam' => $this->nip_perekam,
            'kode_pengesahan' => $this->kode_pengesahan,
            'kode_cabang' => $this->kode_cabang,
            'nama_channel' => $this->nama_channel,
            'status_pembayaran_pajak' => $this->status_pembayaran_pajak,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
