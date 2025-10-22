<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SP2DKirimResource extends JsonResource
{
    public function toArray($request)
    {
        return [
           'id' => $this->id,
            'tahun' => $this->tahun,
            'id_berkas' => $this->id_berkas,
            'id_penerima' => $this->id_penerima,
            'nama_penerima' => $this->nama_penerima,
            'id_operator' => $this->id_operator,
            'nama_operator' => $this->nama_operator,
            'namafile' => $this->namafile,
            'nama_file_asli' => $this->nama_file_asli,
            'tanggal_upload' => $this->tanggal_upload,
            'keterangan' => $this->keterangan,
            'diterima' => $this->diterima,
            'ditolak' => $this->ditolak,
            'tte' => $this->tte,
            'status' => $this->status,
            'tgl_tte' => $this->tgl_tte,
            'alasan_tolak' => $this->alasan_tolak,
            'tgl_kirim_kebank' => $this->tgl_kirim_kebank,
            'id_penandatangan' => $this->id_penandatangan,
            'nama_penandatangan' => $this->nama_penandatangan,
            'file_tte' => $this->file_tte,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'publish' => $this->publish,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
