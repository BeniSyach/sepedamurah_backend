<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SKPPModel extends Model
{
    use SoftDeletes;

    protected $connection = 'oracle';
    protected $table = 'data_skpp';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nomor_surat',
        'lembar',
        'pejabat_menerangkan',
        'nip',
        'nama',
        'tgl_lahir',
        'golongan',
        'unit_kerja',
        'jabatan',
        'sk_dari',
        'nomor_sk',
        'tgl_sk',
        'tmt_sk',
        'mutasi_ke',
        'gaji_terakhir_bulan',
        'gaji_pokok',
        'tunj_istri_suami',
        'tunj_anak',
        'tunj_perb_penghasilan',
        'tunj_struktural',
        'tunj_fungsional',
        'tunj_beras',
        'tunj_umum',
        'tunj_fungsi_khusus',
        'tunj_kemahalan_daerah',
        'tunj_daerah_terpencil',
        'tunj_pajak',
        'tunj_kesehatan',
        'tunj_jkk',
        'tunj_jkm',
        'pembulatan',
        'jumlah_kotor',
        'pot_iwp_1',
        'pot_iwp_8',
        'pot_askes',
        'pot_pajak',
        'pot_bulog',
        'pot_taperum',
        'iuran_korpri',
        'pot_sewa_rumah',
        'pot_hutang',
        'pot_jkk',
        'pot_jkm',
        'jumlah_potongan',
        'jumlah_bersih',
        'tanggungan',
        'jumlah_hutang',
        'keterangan_hutang',
        'tempat_penetapan',
        'tgl_penetapan',
        'jabatan_penandatangan',
        'nama_penandatangan',
        'nip_penandatangan',
        'tembusan',
        'catatan',
        'status',
        'users_id',
        'alasan_tolak',
        'tgl_diterima',
        'tgl_ditolak',
        'operator_id',
        'operator_nama',
        'status_tte',
        'berkas_tte',
        'kd_opd1',
        'kd_opd2',
        'kd_opd3',
        'kd_opd4',
        'kd_opd5',
        'jenis_skpp',
        'nomor_urut',
        'pensiun_pokok',
        'alasan_pensiun',
        'berkas_pengantar_opd',
        'berkas_sk',
        'berkas_daftar_gaji',
        'berkas_kk',
        'berkas_pendukung',
        'verifikasi_berkas',
        'catatan_perbaikan',
    ];

    protected $appends = ['hutang', 'berkas_urls', 'pembuat', 'tanggal_upload'];

    public function getPembuatAttribute()
    {
        return [
            'id' => $this->users_id,
            'nama' => $this->user ? $this->user->name : '-',
            'nip' => $this->user ? $this->user->nip : '-',
            'email' => $this->user ? $this->user->email : null,
        ];
    }

    public function getTanggalUploadAttribute()
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null;
    }

    public function getBerkasUrlsAttribute()
    {
        $baseUrl = url('/storage');
        return [
            'berkas_pengantar_opd' => $this->berkas_pengantar_opd ? "{$baseUrl}/{$this->berkas_pengantar_opd}" : null,
            'berkas_sk' => $this->berkas_sk ? "{$baseUrl}/{$this->berkas_sk}" : null,
            'berkas_daftar_gaji' => $this->berkas_daftar_gaji ? "{$baseUrl}/{$this->berkas_daftar_gaji}" : null,
            'berkas_kk' => $this->berkas_kk ? "{$baseUrl}/{$this->berkas_kk}" : null,
            'berkas_pendukung' => $this->berkas_pendukung ? "{$baseUrl}/{$this->berkas_pendukung}" : null,
        ];
    }

    public function getHutangAttribute()
    {
        return [
            'jumlah_hutang' => (float) ($this->attributes['jumlah_hutang'] ?? 0),
            'keterangan' => $this->attributes['keterangan_hutang'] ?? '-',
        ];
    }

    protected $casts = [
        'tanggungan' => 'array',
        'tembusan' => 'array',
        'verifikasi_berkas' => 'array',
        'gaji_pokok' => 'float',
        'tunj_istri_suami' => 'float',
        'tunj_anak' => 'float',
        'tunj_perb_penghasilan' => 'float',
        'tunj_struktural' => 'float',
        'tunj_fungsional' => 'float',
        'tunj_beras' => 'float',
        'tunj_umum' => 'float',
        'tunj_fungsi_khusus' => 'float',
        'tunj_kemahalan_daerah' => 'float',
        'tunj_daerah_terpencil' => 'float',
        'tunj_pajak' => 'float',
        'tunj_kesehatan' => 'float',
        'tunj_jkk' => 'float',
        'tunj_jkm' => 'float',
        'pembulatan' => 'float',
        'jumlah_kotor' => 'float',
        'pot_iwp_1' => 'float',
        'pot_iwp_8' => 'float',
        'pot_askes' => 'float',
        'pot_pajak' => 'float',
        'pot_bulog' => 'float',
        'pot_taperum' => 'float',
        'iuran_korpri' => 'float',
        'pot_sewa_rumah' => 'float',
        'pot_hutang' => 'float',
        'pot_jkk' => 'float',
        'pot_jkm' => 'float',
        'jumlah_potongan' => 'float',
        'jumlah_bersih' => 'float',
        'jumlah_hutang' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function getSkpdAttribute()
    {
        if (!$this->kd_opd1 || !$this->kd_opd2) {
            return null;
        }

        return SKPDModel::where('kd_opd1', $this->kd_opd1)
            ->where('kd_opd2', $this->kd_opd2)
            ->where('kd_opd3', $this->kd_opd3)
            ->where('kd_opd4', $this->kd_opd4)
            ->where('kd_opd5', $this->kd_opd5)
            ->first();
    }
}
