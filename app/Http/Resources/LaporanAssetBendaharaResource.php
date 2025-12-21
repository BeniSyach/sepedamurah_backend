<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanAssetBendaharaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,

            // ======================
            // KODE OPD
            // ======================
            'kd_opd1'   => $this->kd_opd1,
            'kd_opd2'   => $this->kd_opd2,
            'kd_opd3'   => $this->kd_opd3,
            'kd_opd4'   => $this->kd_opd4,
            'kd_opd5'   => $this->kd_opd5,

            // ======================
            // DATA UTAMA
            // ======================
            'tahun'           => $this->tahun,
            'ref_asset_id'    => $this->ref_asset_id,
            'nama_asset'      => $this->refAssetBendahara?->nm_asset_bendahara,

            // ======================
            // STATUS PROSES
            // ======================
            'proses'            => $this->proses,
            'diterima'          => $this->diterima,
            'ditolak'           => $this->ditolak,
            'alasan_tolak'      => $this->alasan_tolak,
            'supervisor_proses' => $this->supervisor_proses,

            // ======================
            // FILE
            // ======================
            'file' => $this->file,

            // ======================
            // OPERATOR & USER
            // ======================
            'user_id'      => $this->user_id,
            'id_operator'  => $this->id_operator,
            'nama_operator'=> $this->nama_operator,

            // ======================
            // ACCESSOR SKPD
            // ======================
            'skpd' => new SKPDResource($this->skpd),

            // ======================
            // RELASI
            // ======================
            'refAssetBendahara' => new RefAssetBendaharaResource(
                $this->whenLoaded('refAssetBendahara')
            ),

            'user' => new UserResource(
                $this->whenLoaded('user')
            ),

            'operator' => new AksesOperatorResource(
                $this->whenLoaded('operator')
            ),

            // ======================
            // TIMESTAMP
            // ======================
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
