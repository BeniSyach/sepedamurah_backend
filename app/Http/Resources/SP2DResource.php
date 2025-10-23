<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SP2DResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_sp2d' => $this->id_sp2d,
            'tahun' => $this->tahun,
            'id_user' => $this->id_user,
            'nama_user' => $this->nama_user,
            'id_operator' => $this->id_operator,
            'nama_operator' => $this->nama_operator,
            'kd_opd1' => $this->kd_opd1,
            'kd_opd2' => $this->kd_opd2,
            'kd_opd3' => $this->kd_opd3,
            'kd_opd4' => $this->kd_opd4,
            'kd_opd5' => $this->kd_opd5,
            'nama_file' => $this->nama_file,
            'nama_file_asli' => $this->nama_file_asli,
            'file_tte' => $this->file_tte,
            'tanggal_upload' => $this->tanggal_upload,
            'kode_file' => $this->kode_file,
            'diterima' => $this->diterima,
            'ditolak' => $this->ditolak,
            'alasan_tolak' => $this->alasan_tolak,
            'proses' => $this->proses,
            'supervisor_proses' => $this->supervisor_proses,
            'urusan' => $this->urusan,
            'kd_ref1' => $this->kd_ref1,
            'kd_ref2' => $this->kd_ref2,
            'kd_ref3' => $this->kd_ref3,
            'kd_ref4' => $this->kd_ref4,
            'kd_ref5' => $this->kd_ref5,
            'kd_ref6' => $this->kd_ref6,
            'no_spm' => $this->no_spm,
            'jenis_berkas' => $this->jenis_berkas,
            'id_berkas' => $this->id_berkas,
            'agreement' => $this->agreement,
            'kd_belanja1' => $this->kd_belanja1,
            'kd_belanja2' => $this->kd_belanja2,
            'kd_belanja3' => $this->kd_belanja3,
            'jenis_belanja' => $this->jenis_belanja,
            'nilai_belanja' => $this->nilai_belanja,
            'status_laporan' => $this->status_laporan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'rekening'    => new RekeningResource($this->whenLoaded('rekening')),
            'sumber_dana' => new SumberDanaResource($this->whenLoaded('sumberDana')),
            'skpd' => new SKPDResource($this->whenLoaded('skpd')),
        ];
    }
}
