<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanFungsionalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'id_pengirim' => $this->id_pengirim,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'nama_pengirim' => $this->nama_pengirim,
            'id_operator' => $this->id_operator,
            'nama_operator' => $this->nama_operator,
            'jenis_berkas' => $this->jenis_berkas,
            'nama_file' => $this->nama_file,
            'nama_file_asli' => $this->nama_file_asli,
            'tanggal_upload' => $this->tanggal_upload,
            'kode_file' => $this->kode_file,
            'tahun' => $this->tahun,
            'diterima' => $this->diterima,
            'ditolak' => $this->ditolak,
            'alasan_tolak' => $this->alasan_tolak,
            'proses' => $this->proses,
            'supervisor_proses' => $this->supervisor_proses,
            'berkas_tte' => $this->berkas_tte,
            'date_created' => $this->date_created,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
