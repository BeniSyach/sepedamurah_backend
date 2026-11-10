<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SKPPModel;
use App\Models\AksesOperatorModel;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\LogTTEModel;
use App\Services\TTE_BSRE;

class SKPPController extends Controller
{
    /**
     * Resolve authenticated user (JWT API guard -> Default guard -> Request user_id fallback)
     */
    protected function getAuthUser(Request $request = null): ?User
    {
        $user = null;
        try {
            $user = auth('api')->user();
        } catch (\Throwable $e) {
            $user = null;
        }

        if (!$user) {
            $user = Auth::user();
        }

        if (!$user && $request && ($request->filled('user_id') || $request->filled('users_id'))) {
            $uid = $request->get('user_id') ?: $request->get('users_id');
            $user = User::find($uid);
        }

        return $user;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $this->getAuthUser($request);
        $userId = $user?->id ?? $request->get('user_id');

        $query = SKPPModel::query()
            ->with(['user:id,name,nip,email'])
            ->whereNull('deleted_at');

        // Cek apakah user adalah Admin / Superadmin
        $isAdmin = false;
        if ($user) {
            $user->loadMissing('rules');
            $isAdmin = $user->rules->contains(function ($r) {
                $name = strtolower($r->rule ?? '');
                return str_contains($name, 'admin') || str_contains($name, 'superadmin');
            });
        }

        // Cek apakah user adalah Operator
        $isOperator = false;
        if ($userId && !$isAdmin) {
            $isOperator = AksesOperatorModel::where('id_operator', $userId)->exists();
            if (!$isOperator && $user) {
                $user->loadMissing('rules');
                $isOperator = $user->rules->contains(function ($r) {
                    return str_contains(strtolower($r->rule ?? ''), 'operator');
                });
            }
        }

        // 1. FILTER AKSES OPERATOR (Hanya tampilkan dokumen SKPP dari OPD yang diampu oleh Operator)
        if ($isOperator && $userId) {
            $operatorSkpds = AksesOperatorModel::where('id_operator', $userId)->get();
            if ($operatorSkpds->isNotEmpty()) {
                $query->where(function ($q) use ($operatorSkpds) {
                    foreach ($operatorSkpds as $op) {
                        $q->orWhere(function ($qSub) use ($op) {
                            $qSub->whereRaw("RTRIM(kd_opd1) = ?", [str_pad($op->kd_opd1, 2, '0', STR_PAD_LEFT)])
                                 ->whereRaw("RTRIM(kd_opd2) = ?", [str_pad($op->kd_opd2, 2, '0', STR_PAD_LEFT)]);
                            if (!empty($op->kd_opd3)) {
                                $qSub->whereRaw("RTRIM(kd_opd3) = ?", [str_pad($op->kd_opd3, 2, '0', STR_PAD_LEFT)]);
                            }
                            if (!empty($op->kd_opd4)) {
                                $qSub->whereRaw("RTRIM(kd_opd4) = ?", [str_pad($op->kd_opd4, 2, '0', STR_PAD_LEFT)]);
                            }
                            if (!empty($op->kd_opd5)) {
                                $qSub->whereRaw("RTRIM(kd_opd5) = ?", [str_pad($op->kd_opd5, 2, '0', STR_PAD_LEFT)]);
                            }
                        });
                    }
                });
            } else {
                // User terdaftar sebagai operator tapi belum diberikan akses OPD
                $query->whereRaw("1 = 2");
            }
        }
        // 2. FILTER BENDAHARA (Hanya tampilkan dokumen SKPP dari OPD Bendahara atau yang dibuat oleh dirinya)
        elseif ($this->isBendaharaOnly($request)) {
            $opd1 = $request->get('kd_opd1') ?: $user?->kd_opd1;
            $opd2 = $request->get('kd_opd2') ?: $user?->kd_opd2;
            if ($opd1 && $opd2) {
                $kdOpd1 = str_pad($opd1, 2, '0', STR_PAD_LEFT);
                $kdOpd2 = str_pad($opd2, 2, '0', STR_PAD_LEFT);
                $query->where(function ($q) use ($request, $user, $kdOpd1, $kdOpd2, $userId) {
                    $q->where(function ($qSub) use ($request, $user, $kdOpd1, $kdOpd2) {
                        $qSub->whereRaw("RTRIM(kd_opd1) = ?", [$kdOpd1])
                             ->whereRaw("RTRIM(kd_opd2) = ?", [$kdOpd2]);
                        $opd3 = $request->get('kd_opd3') ?: $user?->kd_opd3;
                        if ($opd3) {
                            $qSub->whereRaw("RTRIM(kd_opd3) = ?", [str_pad($opd3, 2, '0', STR_PAD_LEFT)]);
                        }
                        $opd4 = $request->get('kd_opd4') ?: $user?->kd_opd4;
                        if ($opd4) {
                            $qSub->whereRaw("RTRIM(kd_opd4) = ?", [str_pad($opd4, 2, '0', STR_PAD_LEFT)]);
                        }
                        $opd5 = $request->get('kd_opd5') ?: $user?->kd_opd5;
                        if ($opd5) {
                            $qSub->whereRaw("RTRIM(kd_opd5) = ?", [str_pad($opd5, 2, '0', STR_PAD_LEFT)]);
                        }
                    });
                    if ($userId) {
                        $q->orWhere('users_id', $userId);
                    }
                });
            } elseif ($userId) {
                $query->where('users_id', $userId);
            }
        }
        // 3. ADMIN / ROLE LAINNYA (Dapat melihat semua atau memfilter berdasarkan OPD jika dikirimkan)
        elseif ($request->filled('kd_opd1') && $request->filled('kd_opd2')) {
            $kdOpd1 = str_pad($request->get('kd_opd1'), 2, '0', STR_PAD_LEFT);
            $kdOpd2 = str_pad($request->get('kd_opd2'), 2, '0', STR_PAD_LEFT);
            $query->where(function ($qSub) use ($request, $kdOpd1, $kdOpd2) {
                $qSub->whereRaw("RTRIM(kd_opd1) = ?", [$kdOpd1])
                     ->whereRaw("RTRIM(kd_opd2) = ?", [$kdOpd2]);
                if ($request->filled('kd_opd3')) {
                    $qSub->whereRaw("RTRIM(kd_opd3) = ?", [str_pad($request->get('kd_opd3'), 2, '0', STR_PAD_LEFT)]);
                }
                if ($request->filled('kd_opd4')) {
                    $qSub->whereRaw("RTRIM(kd_opd4) = ?", [str_pad($request->get('kd_opd4'), 2, '0', STR_PAD_LEFT)]);
                }
                if ($request->filled('kd_opd5')) {
                    $qSub->whereRaw("RTRIM(kd_opd5) = ?", [str_pad($request->get('kd_opd5'), 2, '0', STR_PAD_LEFT)]);
                }
            });
        }

        // Filter Status / Menu
        $filterTanggal = 'created_at';
        if ($menu = $request->get('menu')) {
            if ($menu === 'diterima') {
                $query->where('status', 'Diterima');
                $filterTanggal = 'tgl_diterima';
            } elseif ($menu === 'ditolak') {
                $query->where('status', 'Ditolak');
                $filterTanggal = 'tgl_ditolak';
            } elseif ($menu === 'permohonan') {
                $query->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhere('status', 'Draft')
                      ->orWhere('status', 'Permohonan');
                });
                $filterTanggal = 'created_at';
            }
        } elseif ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Filter Tanggal (date_from & date_to) mirip SP2D
        if ($request->filled('filter_tanggal')) {
            $filterTanggal = $request->get('filter_tanggal');
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate($filterTanggal, '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate($filterTanggal, '<=', $dateTo);
        }

        // Filter Nomor Surat
        if ($nomor = $request->get('nomor')) {
            $nomorLower = strtolower($nomor);
            $query->whereRaw("LOWER(nomor_surat) LIKE ?", ["%{$nomorLower}%"]);
        }

        // Filter Jenis SKPP (Mutasi / Pensiun)
        if ($jenis = $request->get('jenis_skpp')) {
            if ($jenis !== 'all') {
                $query->whereRaw("LOWER(jenis_skpp) = ?", [strtolower(trim($jenis))]);
            }
        }

        // Search (Pencarian fleksibel di berbagai kolom & relasi user)
        if ($search = $request->get('search')) {
            $search = strtolower($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(nomor_surat) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(nama) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(nip) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(unit_kerja) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(mutasi_ke) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(operator_nama) LIKE ?", ["%{$search}%"])
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"])
                         ->orWhereRaw("LOWER(nip) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        // Filter Tahun
        if ($tahun = $request->get('tahun')) {
            $query->where(function ($q) use ($tahun) {
                $q->whereRaw("nomor_surat LIKE ?", ["%{$tahun}%"])
                  ->orWhereRaw("tgl_penetapan LIKE ?", ["%{$tahun}%"])
                  ->orWhereRaw("gaji_terakhir_bulan LIKE ?", ["%{$tahun}%"]);
            });
        }

        // Sorting aman (Mencegah ORA-00904 jika sort_by tanggal_upload / kolom lain yang belum terdaftar)
        $allowedSort = [
            'id'             => 'id',
            'tanggal_upload' => 'created_at',
            'created_at'     => 'created_at',
            'tgl_penetapan'  => 'tgl_penetapan',
            'tgl_diterima'   => 'tgl_diterima',
            'tgl_ditolak'    => 'tgl_ditolak',
            'nomor_surat'    => 'nomor_surat',
            'nama'           => 'nama',
            'nip'            => 'nip',
            'unit_kerja'     => 'unit_kerja',
            'status'         => 'status',
            'jumlah_kotor'   => 'jumlah_kotor',
            'jumlah_bersih'  => 'jumlah_bersih',
        ];
        $sortBy = $allowedSort[$request->get('sort_by')] ?? 'id';
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = (int) ($request->get('per_page') ?: $request->get('perPage') ?: 10);
        $data = $query->paginate($perPage);

        // Transform collection agar relasi pembuat & tanggal upload ikut
        $data->getCollection()->transform(function ($item) {
            $item->pembuat = $item->pembuat;
            $item->tanggal_upload = $item->tanggal_upload;
            $item->skpd = $item->skpd;
            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar data SKPP berhasil diambil',
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function getNextNomorUrut($tahun = null): int
    {
        $tahun = $tahun ?: date('Y');

        $maxUrut = SKPPModel::where(function ($q) use ($tahun) {
            $q->whereRaw("nomor_surat LIKE ?", ["%/{$tahun}%"])
              ->orWhereRaw("tgl_penetapan LIKE ?", ["%{$tahun}%"]);
        })->max('nomor_urut');

        $highest = 0;
        $existing = SKPPModel::where(function ($q) use ($tahun) {
            $q->whereRaw("nomor_surat LIKE ?", ["%/{$tahun}%"])
              ->orWhereRaw("tgl_penetapan LIKE ?", ["%{$tahun}%"]);
        })->pluck('nomor_surat');

        foreach ($existing as $ns) {
            if (preg_match('/900\/\s*(\d+)\s*\/SKPP/i', $ns, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        $next = max((int) $maxUrut, $highest) + 1;
        return $next > 0 ? $next : 1;
    }

    public function generateNomorPreview(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $nextUrut = $this->getNextNomorUrut($tahun);
        $padded = str_pad($nextUrut, 2, '0', STR_PAD_LEFT);
        $nomorSurat = "900/ {$padded} /SKPP/KEU/SB/{$tahun}";

        return response()->json([
            'success' => true,
            'nomor_urut' => $nextUrut,
            'nomor_surat' => $nomorSurat,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:30',
            'unit_kerja' => 'required|string|max:255',
            'sk_dari' => 'required|string|max:255',
            'nomor_sk' => 'required|string|max:100',
            'tgl_sk' => 'required|string|max:20',
            'tmt_sk' => 'required|string|max:20',
            'berkas_pengantar_opd' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkas_sk' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkas_daftar_gaji' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkas_kk' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkas_pendukung' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'nama.required' => 'Nama pegawai wajib diisi',
            'nip.required' => 'NIP pegawai wajib diisi',
            'unit_kerja.required' => 'Unit Kerja (OPD) wajib dipilih',
            'sk_dari.required' => 'Instansi/Pejabat penerbit SK wajib diisi',
            'nomor_sk.required' => 'Nomor SK wajib diisi',
            'tgl_sk.required' => 'Tanggal SK wajib diisi',
            'tmt_sk.required' => 'TMT SK wajib diisi',
            'berkas_pengantar_opd.required' => 'Berkas Surat Pengantar OPD wajib diunggah',
            'berkas_sk.required' => 'Berkas SK (Pensiun/Mutasi) wajib diunggah',
            'berkas_daftar_gaji.required' => 'Berkas Daftar Gaji Terakhir wajib diunggah',
            'berkas_kk.required' => 'Berkas Kartu Keluarga wajib diunggah',
            'berkas_pendukung.required' => 'Berkas Dokumen Pendukung wajib diunggah',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->all();

            // Decode stringified JSON fields if sent via FormData
            foreach (['tanggungan', 'tembusan', 'hutang', 'verifikasi_berkas'] as $jsonField) {
                if ($request->has($jsonField) && is_string($request->input($jsonField))) {
                    $decoded = json_decode($request->input($jsonField), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$jsonField] = $decoded;
                    }
                }
            }

            // Auto-generate nomor_surat jika tidak dikirimkan atau kosong
            if (empty($data['nomor_surat'])) {
                $tahun = !empty($data['tgl_penetapan']) ? date('Y', strtotime($data['tgl_penetapan'])) : date('Y');
                $nextUrut = $this->getNextNomorUrut($tahun);
                $padded = str_pad($nextUrut, 2, '0', STR_PAD_LEFT);
                $data['nomor_urut'] = $nextUrut;
                $data['nomor_surat'] = "900/ {$padded} /SKPP/KEU/SB/{$tahun}";
            } elseif (!isset($data['nomor_urut']) && preg_match('/900\/\s*(\d+)\s*\/SKPP/i', $data['nomor_surat'], $m)) {
                $data['nomor_urut'] = (int) $m[1];
            }

            // Default jenis_skpp
            if (empty($data['jenis_skpp'])) {
                $data['jenis_skpp'] = 'mutasi';
            }

            // Hitung otomatis Jumlah Kotor (A)
            $penerimaanFields = [
                'gaji_pokok', 'tunj_istri_suami', 'tunj_anak', 'tunj_perb_penghasilan',
                'tunj_struktural', 'tunj_fungsional', 'tunj_beras', 'tunj_umum',
                'tunj_fungsi_khusus', 'tunj_kemahalan_daerah', 'tunj_daerah_terpencil',
                'tunj_pajak', 'tunj_kesehatan', 'tunj_jkk', 'tunj_jkm', 'pembulatan'
            ];
            $kotor = 0;
            foreach ($penerimaanFields as $f) {
                $kotor += (float) ($data[$f] ?? 0);
            }
            $data['jumlah_kotor'] = $kotor;

            // Hitung otomatis Jumlah Potongan (B)
            $potonganFields = [
                'pot_iwp_1', 'pot_iwp_8', 'pot_askes', 'pot_pajak', 'pot_bulog',
                'pot_taperum', 'iuran_korpri', 'pot_sewa_rumah', 'pot_hutang',
                'pot_jkk', 'pot_jkm'
            ];
            $potongan = 0;
            foreach ($potonganFields as $f) {
                $potongan += (float) ($data[$f] ?? 0);
            }
            $data['jumlah_potongan'] = $potongan;

            // Jumlah Bersih
            $data['jumlah_bersih'] = $kotor - $potongan;

            // Tangani data hutang dari request
            if ($request->has('hutang') && is_array($request->input('hutang'))) {
                $data['jumlah_hutang'] = (float) ($request->input('hutang.jumlah_hutang') ?? 0);
                $data['keterangan_hutang'] = $request->input('hutang.keterangan') ?? '-';
            }

            // Handle upload berkas
            $fileFields = [
                'berkas_pengantar_opd',
                'berkas_sk',
                'berkas_daftar_gaji',
                'berkas_kk',
                'berkas_pendukung',
            ];
            foreach ($fileFields as $f) {
                if ($request->hasFile($f)) {
                    $file = $request->file($f);
                    $ext = $file->getClientOriginalExtension();
                    $fname = "skpp_{$f}_" . time() . "_" . uniqid() . ".{$ext}";
                    $path = $file->storeAs('skpp_berkas', $fname, 'public');
                    $data[$f] = $path;
                }
            }

            // User yang membuat
            $user = $this->getAuthUser($request);
            $creatorId = $user?->id ?? ($request->input('user_id') ?: $request->input('users_id'));
            $data['users_id'] = $creatorId;

            if ($user) {
                if (empty($data['kd_opd1']) && !empty($user->kd_opd1)) {
                    $data['kd_opd1'] = $user->kd_opd1;
                    $data['kd_opd2'] = $user->kd_opd2;
                    $data['kd_opd3'] = $user->kd_opd3;
                    $data['kd_opd4'] = $user->kd_opd4;
                    $data['kd_opd5'] = $user->kd_opd5;
                }
            }

            // Normalisasi kd_opd agar 2 digit dengan leading zero jika ada
            foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $f) {
                if (isset($data[$f]) && $data[$f] !== null && $data[$f] !== '') {
                    $data[$f] = str_pad($data[$f], 2, '0', STR_PAD_LEFT);
                }
            }

            // Saat permohonan SKPP baru dibuat, pejabat penandatangan dikosongkan (ditentukan saat pemeriksaan oleh operator/pemeriksa)
            unset(
                $data['penandatangan_id'],
                $data['penandatangan_nik'],
                $data['nama_penandatangan'],
                $data['nip_penandatangan'],
                $data['jabatan_penandatangan']
            );

            // Jika role pembuat adalah Bendahara, status wajib Draft
            if ($this->isBendaharaOnly($request)) {
                $data['status'] = 'Draft';
            }

            $skpp = SKPPModel::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data SKPP berhasil disimpan',
                'data'    => $skpp,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data SKPP',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $skpp = SKPPModel::find($id);

        if (!$skpp) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKPP tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data SKPP ditemukan',
            'data'    => $skpp,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $skpp = SKPPModel::find($id);

        if (!$skpp) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKPP tidak ditemukan',
            ], 404);
        }

        try {
            $data = $request->all();

            // Decode stringified JSON fields if sent via FormData
            foreach (['tanggungan', 'tembusan', 'hutang', 'verifikasi_berkas'] as $jsonField) {
                if ($request->has($jsonField) && is_string($request->input($jsonField))) {
                    $decoded = json_decode($request->input($jsonField), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$jsonField] = $decoded;
                    }
                }
            }

            // Pertahankan nomor surat yang sudah ada jika kosong di request
            if (empty($data['nomor_surat'])) {
                $data['nomor_surat'] = $skpp->nomor_surat;
            }

            // Hitung otomatis Jumlah Kotor (A)
            $penerimaanFields = [
                'gaji_pokok', 'tunj_istri_suami', 'tunj_anak', 'tunj_perb_penghasilan',
                'tunj_struktural', 'tunj_fungsional', 'tunj_beras', 'tunj_umum',
                'tunj_fungsi_khusus', 'tunj_kemahalan_daerah', 'tunj_daerah_terpencil',
                'tunj_pajak', 'tunj_kesehatan', 'tunj_jkk', 'tunj_jkm', 'pembulatan'
            ];
            $kotor = 0;
            foreach ($penerimaanFields as $f) {
                $kotor += (float) (isset($data[$f]) ? $data[$f] : $skpp->{$f});
            }
            $data['jumlah_kotor'] = $kotor;

            // Hitung otomatis Jumlah Potongan (B)
            $potonganFields = [
                'pot_iwp_1', 'pot_iwp_8', 'pot_askes', 'pot_pajak', 'pot_bulog',
                'pot_taperum', 'iuran_korpri', 'pot_sewa_rumah', 'pot_hutang',
                'pot_jkk', 'pot_jkm'
            ];
            $potongan = 0;
            foreach ($potonganFields as $f) {
                $potongan += (float) (isset($data[$f]) ? $data[$f] : $skpp->{$f});
            }
            $data['jumlah_potongan'] = $potongan;

            // Jumlah Bersih
            $data['jumlah_bersih'] = $kotor - $potongan;

            // Tangani data hutang dari request
            if ($request->has('hutang') && is_array($request->input('hutang'))) {
                $data['jumlah_hutang'] = (float) ($request->input('hutang.jumlah_hutang') ?? 0);
                $data['keterangan_hutang'] = $request->input('hutang.keterangan') ?? '-';
            }

            // Handle upload berkas
            $fileFields = [
                'berkas_pengantar_opd',
                'berkas_sk',
                'berkas_daftar_gaji',
                'berkas_kk',
                'berkas_pendukung',
            ];
            foreach ($fileFields as $f) {
                if ($request->hasFile($f)) {
                    // Hapus file lama jika ada
                    if ($skpp->{$f} && Storage::disk('public')->exists($skpp->{$f})) {
                        Storage::disk('public')->delete($skpp->{$f});
                    }
                    $file = $request->file($f);
                    $ext = $file->getClientOriginalExtension();
                    $fname = "skpp_{$f}_" . time() . "_" . uniqid() . ".{$ext}";
                    $path = $file->storeAs('skpp_berkas', $fname, 'public');
                    $data[$f] = $path;
                }
            }

            // Normalisasi kd_opd agar 2 digit dengan leading zero jika ada
            foreach (['kd_opd1', 'kd_opd2', 'kd_opd3', 'kd_opd4', 'kd_opd5'] as $f) {
                if (isset($data[$f]) && $data[$f] !== null && $data[$f] !== '') {
                    $data[$f] = str_pad($data[$f], 2, '0', STR_PAD_LEFT);
                }
            }

            // Jika user adalah Bendahara, status tidak dapat di-acc/diubah sendiri
            if ($this->isBendaharaOnly()) {
                $data['status'] = $skpp->status ?? 'Draft';
            }

            $skpp->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Data SKPP berhasil diperbarui',
                'data'    => $skpp,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data SKPP',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $skpp = SKPPModel::find($id);

        if (!$skpp) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKPP tidak ditemukan',
            ], 404);
        }

        try {
            $skpp->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data SKPP berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data SKPP',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function findSkppSafely($id)
    {
        if (is_numeric($id)) {
            $skpp = SKPPModel::find($id);
            if ($skpp) return $skpp;
        }

        $numericId = preg_replace('/[^0-9]/', '', (string) $id);
        if (!empty($numericId)) {
            $skpp = SKPPModel::find((int) $numericId);
            if ($skpp) return $skpp;
        }

        return SKPPModel::whereNull('deleted_at')->first();
    }

    /**
     * Generate & Download PDF for the given SKPP.
     */
    public function downloadPdf($id)
    {
        $skpp = $this->findSkppSafely($id);

        if (!$skpp) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKPP tidak ditemukan',
            ], 404);
        }

        $pdf = Pdf::loadView('pdf.skpp_pdf', compact('skpp'))
                  ->setPaper('legal', 'portrait');

        $cleanNomor = str_replace(['/', ' '], '_', $skpp->nomor_surat);
        return $pdf->stream("SKPP_{$cleanNomor}.pdf");
    }

    /**
     * Helper untuk cek apakah user yang login hanya memiliki role Bendahara
     */
    private function isBendaharaOnly(Request $request = null): bool
    {
        $user = $this->getAuthUser($request);
        if (!$user) return false;

        $user->loadMissing('rules');
        $rules = $user->rules;
        if (!$rules || $rules->isEmpty()) return false;

        $hasBendahara = $rules->contains(function ($r) {
            return str_contains(strtolower($r->rule ?? ''), 'bendahara');
        });

        $hasAdminOrOther = $rules->contains(function ($r) {
            return !str_contains(strtolower($r->rule ?? ''), 'bendahara');
        });

        return $hasBendahara && !$hasAdminOrOther;
    }

    /**
     * Terima Permohonan SKPP.
     */
    public function terima(Request $request, $id)
    {
        if ($this->isBendaharaOnly($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Bendahara tidak memiliki hak akses untuk menyetujui permohonan SKPP',
            ], 403);
        }

        try {
            $skpp = $this->findSkppSafely($id);

            if (!$skpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SKPP tidak ditemukan',
                ], 404);
            }

            $authUser = $this->getAuthUser($request);
            $operatorId = $authUser?->id ?? $request->input('id_operator', '0');
            $operatorNama = $authUser?->name ?? $request->input('nama_operator', 'Operator BPKAD');

            $skpp->update([
                'status'        => 'Diterima',
                'tgl_diterima'  => now()->format('Y-m-d H:i:s'),
                'operator_id'   => (string) $operatorId,
                'operator_nama' => $operatorNama,
                'alasan_tolak'  => null,
                'tgl_ditolak'   => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan SKPP berhasil diterima',
                'data'    => $skpp,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Gagal menerima SKPP: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerima SKPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tolak Permohonan SKPP.
     */
    public function tolak(Request $request, $id)
    {
        if ($this->isBendaharaOnly($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Bendahara tidak memiliki hak akses untuk menolak permohonan SKPP',
            ], 403);
        }

        try {
            $skpp = $this->findSkppSafely($id);

            if (!$skpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SKPP tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'alasan_tolak' => 'required|string|max:1000',
            ], [
                'alasan_tolak.required' => 'Alasan penolakan wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $authUser = $this->getAuthUser($request);
            $operatorId = $authUser?->id ?? $request->input('id_operator', '0');
            $operatorNama = $authUser?->name ?? $request->input('nama_operator', 'Operator BPKAD');

            $skpp->update([
                'status'        => 'Ditolak',
                'tgl_ditolak'   => now()->format('Y-m-d H:i:s'),
                'alasan_tolak'  => $request->alasan_tolak,
                'operator_id'   => (string) $operatorId,
                'operator_nama' => $operatorNama,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan SKPP berhasil ditolak',
                'data'    => $skpp,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Gagal menolak SKPP: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak SKPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifikasi / Pemeriksaan Berkas SKPP oleh Operator.
     */
    public function verifikasi(Request $request, $id)
    {
        if ($this->isBendaharaOnly($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Bendahara tidak memiliki hak akses untuk memeriksa berkas SKPP',
            ], 403);
        }

        try {
            $skpp = $this->findSkppSafely($id);

            if (!$skpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SKPP tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:Diterima,Perlu Perbaikan,Ditolak,Draft',
                'alasan' => 'nullable|string',
                'verifikasi_berkas' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $verifBerkas = $request->input('verifikasi_berkas');
            if (is_string($verifBerkas)) {
                $verifBerkas = json_decode($verifBerkas, true) ?: [];
            }
            if (!is_array($verifBerkas)) {
                $verifBerkas = [];
            }

            // Hitung status berkas & kumpulkan alasan masing-masing berkas
            $hasTidakValid = false;
            $hasPerluPerbaikan = false;
            $alasanTolakList = [];
            $alasanPerbaikanList = [];

            $labelBerkas = [
                'draft_skpp'           => 'Draft SKPP Resmi',
                'berkas_pengantar_opd' => 'Surat Pengantar OPD',
                'berkas_sk'            => 'SK Pensiun / Mutasi',
                'berkas_daftar_gaji'   => 'Daftar Gaji Terakhir',
                'berkas_kk'            => 'FC Kartu Keluarga',
                'berkas_pendukung'     => 'Dokumen Pendukung',
            ];

            foreach ($verifBerkas as $docKey => $item) {
                $itemStatus = is_array($item) ? ($item['status'] ?? '') : (string) $item;
                $itemAlasan = is_array($item) ? ($item['alasan'] ?? '') : '';
                $docLabel = $labelBerkas[$docKey] ?? ucwords(str_replace('_', ' ', $docKey));

                if ($itemStatus === 'tidak_valid') {
                    $hasTidakValid = true;
                    if (!empty($itemAlasan)) {
                        $alasanTolakList[] = "• {$docLabel}: {$itemAlasan}";
                    }
                } elseif ($itemStatus === 'perlu_perbaikan') {
                    $hasPerluPerbaikan = true;
                    if (!empty($itemAlasan)) {
                        $alasanPerbaikanList[] = "• {$docLabel}: {$itemAlasan}";
                    }
                }
            }

            // Logika otomatis: jika salah satu tidak valid maka skpp tersebut ditolak
            if ($hasTidakValid) {
                $status = 'Ditolak';
                $alasan = !empty($alasanTolakList) ? implode("\n", $alasanTolakList) : ($request->input('alasan') ?? 'Salah satu berkas tidak valid');
            } elseif ($hasPerluPerbaikan) {
                $status = 'Perlu Perbaikan';
                $alasan = !empty($alasanPerbaikanList) ? implode("\n", $alasanPerbaikanList) : ($request->input('alasan') ?? 'Ada berkas yang perlu perbaikan');
            } else {
                $status = $request->input('status') ?: 'Diterima';
                $alasan = null;
            }

            $authUser = $this->getAuthUser($request);
            $operatorId = $authUser?->id ?? $request->input('id_operator', '0');
            $operatorNama = $authUser?->name ?? $request->input('nama_operator', 'Operator BPKAD');

            $updateData = [
                'status'            => $status,
                'operator_id'       => (string) $operatorId,
                'operator_nama'     => $operatorNama,
                'verifikasi_berkas' => $verifBerkas,
            ];

            if ($status === 'Diterima') {
                $updateData['tgl_diterima'] = now()->format('Y-m-d H:i:s');
                $updateData['alasan_tolak'] = null;
                $updateData['catatan_perbaikan'] = null;

                if ($request->filled('penandatangan_id')) {
                    $updateData['penandatangan_id'] = (string) $request->penandatangan_id;
                }
                if ($request->filled('penandatangan_nik')) {
                    $updateData['penandatangan_nik'] = (string) $request->penandatangan_nik;
                }
                if ($request->filled('nama_penandatangan')) {
                    $updateData['nama_penandatangan'] = (string) $request->nama_penandatangan;
                }
                if ($request->filled('nip_penandatangan')) {
                    $updateData['nip_penandatangan'] = (string) $request->nip_penandatangan;
                }
                if ($request->filled('jabatan_penandatangan')) {
                    $updateData['jabatan_penandatangan'] = (string) $request->jabatan_penandatangan;
                }
                if ($request->filled('tempat_penetapan')) {
                    $updateData['tempat_penetapan'] = (string) $request->tempat_penetapan;
                }
                if ($request->filled('tgl_penetapan')) {
                    $updateData['tgl_penetapan'] = (string) $request->tgl_penetapan;
                }
            } elseif ($status === 'Ditolak') {
                $updateData['tgl_ditolak'] = now()->format('Y-m-d H:i:s');
                $updateData['alasan_tolak'] = $alasan;
                $updateData['catatan_perbaikan'] = null;
            } elseif ($status === 'Perlu Perbaikan') {
                $updateData['catatan_perbaikan'] = $alasan;
                $updateData['alasan_tolak'] = null;
            }

            $skpp->update($updateData);

            return response()->json([
                'success' => true,
                'message' => "Pemeriksaan berkas SKPP berhasil disimpan dengan status: {$status}",
                'data'    => $skpp,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Gagal memverifikasi SKPP: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pemeriksaan SKPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View / Stream File Berkas Uploaded SKPP
     */
    public function viewBerkas($id, $field)
    {
        $skpp = $this->findSkppSafely($id);
        if (!$skpp) {
            return response()->json(['message' => 'Data SKPP tidak ditemukan'], 404);
        }

        $allowedFields = [
            'berkas_pengantar_opd',
            'berkas_sk',
            'berkas_daftar_gaji',
            'berkas_kk',
            'berkas_pendukung',
            'berkas_tte',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['message' => 'Field berkas tidak valid'], 400);
        }

        $path = $skpp->{$field};
        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File berkas tidak ditemukan di server'], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        return response()->file($fullPath);
    }

    /**
     * Tanda Tangan Elektronik (TTE) Dokumen SKPP.
     */
    public function sign(Request $request)
    {
            if ($this->isBendaharaOnly($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Bendahara tidak memiliki hak akses untuk menandatangani (TTE) dokumen SKPP',
                ], 403);
            }

            $request->validate([
                'id'         => 'required',
                'passphrase' => 'required|string',
                'nama_file'  => 'nullable|string',
            ]);

            $id = $request->id;
            $skpp = $this->findSkppSafely($id);

            if (!$skpp) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data SKPP tidak ditemukan',
                ], 404);
            }

            $user = $this->getAuthUser($request);

            // Verifikasi bahwa user yang login adalah penandatangan yang dituju
            if ($user && $skpp->penandatangan_id && (string)$skpp->penandatangan_id !== (string)$user->id) {
                $nikMatches = $skpp->penandatangan_nik && $user->nik && (string)$skpp->penandatangan_nik === (string)$user->nik;
                $nipMatches = $skpp->nip_penandatangan && $user->nip && (string)$skpp->nip_penandatangan === (string)$user->nip;
                if (!$nikMatches && !$nipMatches) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Anda bukan pejabat penandatangan yang ditugaskan untuk menandatangani dokumen SKPP ini',
                    ], 403);
                }
            }

            $nik = $user->nik ?? '1234567890123456';
            $namaFile = $request->input('nama_file', 'SKPP_' . str_replace(['/', ' '], '_', $skpp->nomor_surat));
            $signedFileName = "signed-skpp-{$skpp->id}-" . time() . ".pdf";
            $savePath = "pdf_tte/" . $signedFileName;

            // Jika ada file PDF yang sudah ditempel QR Code/Visual TTE dari frontend
            if ($request->hasFile('file')) {
                $uploaded = $request->file('file');
                $originalFilePath = $uploaded->storeAs("skpp_original", "orig-{$skpp->id}-" . time() . ".pdf", "public");
                $fullPath = storage_path("app/public/" . $originalFilePath);

                // Coba sign melalui BSRE jika tersedia
                $service = new TTE_BSRE();
                $result = $service->signPdf(
                    $fullPath,
                    $nik,
                    $request->passphrase,
                    $request->input('tampilan', 'invisible'),
                    $namaFile,
                    $skpp->id
                );

                 $errorCode = null;
        if (isset($result['detail']) && is_array($result['detail']) && isset($result['detail']['status_code'])) {
            $errorCode = $result['detail']['status_code'];
        }

        // ================================
        // LOG JIKA TTE GAGAL
        // ================================
        if ($result['status'] != 'success') {
            $detailRaw = $result['detail'] ?? null;

            if (is_string($detailRaw)) {
                // Jika berupa JSON string → decode
                $detail = json_decode($detailRaw, true);
            } elseif (is_array($detailRaw)) {
                // Jika sudah array → langsung pakai
                $detail = $detailRaw;
            } else {
                $detail = [];
            }
            
            $errorMsg = $detail['error'] ?? ($result['message'] ?? 'Unknown error');
            LogTTEModel::create([
                'id_berkas'         => $request->id,
                'kategori'          => 'SKPP',
                'tte'               => 'Error',
                'status'            => 0,
                'tgl_tte'           => now(),
                'keterangan'        => "Gagal tandatangan dokumen SKPP - $errorMsg",
                'message'           => $errorMsg,
                'id_penandatangan'  => $user->id ?? '0',
                'nama_penandatangan'=> $user->name ?? 'User TTE',
                'date_created'      => now(),
            ]);

            return response()->json([
                'status'     => 'error',
                'message'    => $result['message'] ?? 'Gagal terhubung ke server BSRE',
                'error_code' => $errorCode,
                'detail'     => $result['detail'] ?? null
            ], 400);
        }

                if (isset($result['status']) && $result['status'] === 'success') {
                    $savePath = $result['file_path'];
                } else {
                    Storage::disk('public')->put($savePath, file_get_contents($fullPath));
                }
            } else {
                // Jika generate langsung dari view blade
                $pdf = Pdf::loadView('pdf.skpp_pdf', compact('skpp'))
                          ->setPaper('legal', 'portrait');
                Storage::disk('public')->put($savePath, $pdf->output());
            }

            $skpp->update([
                'status_tte' => 'Sudah TTE',
                'berkas_tte' => $savePath,
            ]);

            return response()->json([
                'status'     => 'success',
                'message'    => 'Dokumen SKPP berhasil ditandatangani secara elektronik',
                'file_path'  => $savePath,
                'data'       => $skpp,
            ]);
    }

    /**
     * Download Berkas PDF yang sudah di-TTE.
     */
    public function downloadTTE($id)
    {
        $skpp = $this->findSkppSafely($id);

        if (!$skpp) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKPP tidak ditemukan',
            ], 404);
        }

        $disk = Storage::disk('public');
        if ($skpp->berkas_tte && $disk->exists($skpp->berkas_tte)) {
            $fileName = "SKPP_TTE_" . str_replace(['/', ' '], '_', $skpp->nomor_surat) . ".pdf";
            return response()->file($disk->path($skpp->berkas_tte), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Dokumen SKPP belum ditandatangani secara elektronik (berkas TTE belum tersedia)',
        ], 404);
    }
}
