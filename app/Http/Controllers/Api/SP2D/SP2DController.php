<?php

namespace App\Http\Controllers\Api\SP2D;

use App\Http\Controllers\Controller;
use App\Models\SP2DModel;
use Illuminate\Http\Request;
use App\Http\Resources\SP2DResource;
use App\Models\AksesOperatorModel;
use App\Models\SP2DRekeningModel;
use App\Models\SP2DSumberDanaModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SP2DController extends Controller
{
    /**
     * List SP2D (pagination + search)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
        $orderColumn = 'tanggal_upload';
        $orderDir    = 'desc';
        // 🔍 Query dasar SP2D + relasi yang bisa di-eager-load
        $query = Sp2dModel::query()
            ->with(['rekening', 'sumberDana', 'sp2dkirim']) // relasi Eloquent valid
            ->whereNull('deleted_at');

        if ($menu = $request->get('menu')) {

            if($menu == 'permohonan_sp2d'){
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
            // ambil data yg belum diperiksa operator
            $query->where('id_operator', '0');
            $query->whereNull('proses');
            $query->whereNull('diterima')->whereNull('ditolak');
            }

            // Operator
            if($menu == 'permohonan_sp2d_operator'){
               // Ambil data SKPD dari operator yang login
               $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();

    
               if ($operatorSkpd) {
                $query->where(function ($q) use ($operatorSkpd) {
                    foreach ($operatorSkpd as $op) {
                        $q->orWhere(function ($q2) use ($op) {
                            $q2->where('kd_opd1', $op->kd_opd1)
                               ->where('kd_opd2', $op->kd_opd2)
                               ->where('kd_opd3', $op->kd_opd3)
                               ->where('kd_opd4', $op->kd_opd4)
                               ->where('kd_opd5', $op->kd_opd5);
                        });
                    }
                });
                
               }
                // ambil data yg belum diperiksa operator
                $query->where('id_operator', '0');
                $query->where('proses', '1');
                $query->whereNotNull('supervisor_proses');
                $query->whereNull('diterima')->whereNull('ditolak');
            }

            if($menu == 'permohonan_sp2d_terima_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('kd_opd1', $op->kd_opd1)
                                ->where('kd_opd2', $op->kd_opd2)
                                ->where('kd_opd3', $op->kd_opd3)
                                ->where('kd_opd4', $op->kd_opd4)
                                ->where('kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('diterima');
            }

            if($menu == 'permohonan_sp2d_tolak_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('kd_opd1', $op->kd_opd1)
                                ->where('kd_opd2', $op->kd_opd2)
                                ->where('kd_opd3', $op->kd_opd3)
                                ->where('kd_opd4', $op->kd_opd4)
                                ->where('kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('ditolak');
            }

            if($menu == 'permohonan_sp2d_kirim_bank_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('kd_opd1', $op->kd_opd1)
                                ->where('kd_opd2', $op->kd_opd2)
                                ->where('kd_opd3', $op->kd_opd3)
                                ->where('kd_opd4', $op->kd_opd4)
                                ->where('kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('diterima');
            }
            
            if($menu == 'permohonan_sp2d_publish_operator'){
                // Ambil data SKPD dari operator yang login
                $operatorSkpd = AksesOperatorModel::where('id_operator', $request->get('user_id'))->get();
 
     
                if ($operatorSkpd) {
                 $query->where(function ($q) use ($operatorSkpd) {
                     foreach ($operatorSkpd as $op) {
                         $q->orWhere(function ($q2) use ($op) {
                             $q2->where('kd_opd1', $op->kd_opd1)
                                ->where('kd_opd2', $op->kd_opd2)
                                ->where('kd_opd3', $op->kd_opd3)
                                ->where('kd_opd4', $op->kd_opd4)
                                ->where('kd_opd5', $op->kd_opd5);
                         });
                     }
                 });
                 
                }
                 // ambil data yg belum diperiksa operator
                 $query->where('id_operator', '0');
                 $query->where('proses', '1');
                 $query->whereNotNull('supervisor_proses');
                 $query->whereNotNull('diterima');
            }

            if($menu == 'berkas_masuk_sp2d'){
                // hanya tampilkan yang belum diverifikasi
                $query->whereNull('diterima')->whereNull('ditolak');
            }

            // ✅ SPD Diterima
            if ($menu === 'sp2d_diterima') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->where('proses', '2');
                $query->whereNotNull('diterima'); // hanya yang sudah diterima
                $orderColumn = 'diterima';
                $orderDir    = 'desc';
            }

            // (opsional) kalau kamu juga punya 'sp2d_ditolak'
            if ($menu === 'sp2d_ditolak') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('ditolak'); // hanya yang ditolak
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_publish_kuasa_bud') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNotNull('publish')
                      ->where('publish', '1');
                });
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_kirim_bank') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('diterima');
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNotNull('tgl_kirim_kebank');
                });
            }

            // (opsional) kalau kamu juga punya 'sp2d_publish_kuasa_bud'
            if ($menu === 'sp2d_tte') {
                if ($userId = $request->get('user_id')) {
                    $query->where('id_user', $userId);
                }
                $query->whereNotNull('diterima');
                $query->whereHas('sp2dkirim', function ($q) {
                    $q->whereNull('tgl_tte');
                });
            }
        }
    
        // 🔎 Pencarian fleksibel
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(nama_user) LIKE ?", ["%".strtolower($search)."%"])
                ->orWhereRaw("LOWER(nama_operator) LIKE ?", ["%".strtolower($search)."%"])
                ->orWhereRaw("LOWER(nama_file) LIKE ?", ["%".strtolower($search)."%"])
                ->orWhereRaw("LOWER(no_spm) LIKE ?", ["%".strtolower($search)."%"]);
            });
        }
    
        // 🔽 Urutan dan pagination
        $data = $query->orderBy($orderColumn, $orderDir)
        ->paginate($perPage);
    
        // ==========================================================
        // 🔗 Transformasi agar accessor & relasi manual ikut tampil
        // ==========================================================
        $data->getCollection()->transform(function ($item) {
            // relasi accessor (akan menjalankan getXxxAttribute)
            $item->program     = $item->program;
            $item->kegiatan    = $item->kegiatan;
            $item->subkegiatan = $item->subkegiatan;
            $item->rekening    = $item->rekening;
            $item->bu          = $item->bu;
            $item->skpd        = $item->skpd;
    
            // kalau SP2D punya relasi rekening (hasMany)
            if ($item->relationLoaded('rekening')) {
                $item->rekening->transform(function ($rek) {
                    $rek->program     = $rek->program;
                    $rek->kegiatan    = $rek->kegiatan;
                    $rek->subkegiatan = $rek->subkegiatan;
                    $rek->rekening    = $rek->rekening;
                    $rek->bu          = $rek->bu;
                    $rek->urusan          = $rek->urusan;
                    return $rek;
                });
            }
    
            // kalau SP2D punya relasi sumberDana
            if ($item->relationLoaded('sumberDana')) {
                $item->sumberDana->transform(function ($sd) {
                    $sd->referensi = $sd->sumberDana;
                    return $sd;
                });
            }
    
            return $item;
        });
    
        // 🔙 Return JSON lengkap dengan pagination meta
        return response()->json([
            'success' => true,
            'message' => 'Daftar SP2D berhasil diambil',
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
     * Store SP2D baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|string|max:4',
            'id_user' => 'required|integer',
            'nama_user' => 'required|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'nama_file' => 'required|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // max 10MB
            'file_tte' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:255',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'urusan' => 'nullable|string',
            'kd_ref1' => 'nullable|string|max:5',
            'kd_ref2' => 'nullable|string|max:5',
            'kd_ref3' => 'nullable|string|max:5',
            'kd_ref4' => 'nullable|string|max:5',
            'kd_ref5' => 'nullable|string|max:5',
            'kd_ref6' => 'nullable|string|max:5',
            'no_spm' => 'nullable|string|max:255',
            'jenis_berkas' => 'nullable|string|max:255',
            'id_berkas' => 'nullable|string',
            'agreement' => 'nullable|string|max:255',
            'kd_belanja1' => 'nullable|string|max:5',
            'kd_belanja2' => 'nullable|string|max:5',
            'kd_belanja3' => 'nullable|string|max:5',
            'jenis_belanja' => 'nullable|string|max:255',
            'nilai_belanja' => 'nullable|string',
            'status_laporan' => 'nullable|string|max:255',
            'sp2d_rek' => 'nullable|string',
            'sumber_dana' => 'required|string'
        ]);
    
        try {
            $folder = 'sp2d/' . date('Ymd');
    
            // Simpan file nama_file_asli jika ada
            if ($request->hasFile('nama_file_asli')) {
                $file = $request->file('nama_file_asli');
                $path = $file->store($folder, 'public');
                $validated['nama_file_asli'] = $path;
            }
    
            // Simpan file file_tte jika ada
            if ($request->hasFile('file_tte')) {
                $fileTte = $request->file('file_tte');
                $pathTte = $fileTte->store($folder, 'public');
                $validated['file_tte'] = $pathTte;
            }
            $kodeFile = Str::random(10);
            // Simpan data ke database
            $sp2d = SP2DModel::create(array_merge($validated, [
                'created_at' => now(),
                'kode_file' => $kodeFile,
                'tanggal_upload' => now(),
            ]));

            // Pastikan data berhasil dibuat sebelum lanjut
            if ($sp2d && $sp2d instanceof SP2DModel) {
                // Ambil ulang data (jika perlu data lengkap dengan relasi)
                $sp2d = SP2DModel::where('kode_file', $kodeFile)->first();

                // Simpan data sp2d_rek jika ada
                if (!empty($validated['sp2d_rek'])) {
                    $sp2dRekPayload = json_decode($validated['sp2d_rek'], true);
                    $this->saveSp2dRekening($sp2d->id_sp2d, $sp2dRekPayload);
                }

                // Simpan data sumber_dana jika ada
                if (!empty($validated['sumber_dana'])) {
                    $sumberDanaPayload = json_decode($validated['sumber_dana'], true);
                    $this->saveSumberDana($sp2d->id_sp2d, $sumberDanaPayload);
                }
            } else {
                // Jika gagal create
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data SP2D.'
                ], 500);
            }
        
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'data' => new SP2DResource($sp2d),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    private function saveSp2dRekening($sp2d_id, $sp2d_rek_payload)
    {
        $insertData = [];
    
        foreach ($sp2d_rek_payload as $urusan) {
            $kd_urusan = $urusan['kd_urusan'];
            
            foreach ($urusan['bidangUrusan'] as $bidang) {
                $kd_bu1 = $bidang['kd_bu1'];
                $kd_bu2 = $bidang['kd_bu2'];
    
                foreach ($bidang['program'] as $program) {
                    $kd_prog1 = $program['kd_prog1'];
                    $kd_prog2 = $program['kd_prog2'];
                    $kd_prog3 = $program['kd_prog3'];
    
                    foreach ($program['kegiatan'] as $kegiatan) {
                        $kd_keg1 = $kegiatan['kd_keg1'];
                        $kd_keg2 = $kegiatan['kd_keg2'];
                        $kd_keg3 = $kegiatan['kd_keg3'];
                        $kd_keg4 = $kegiatan['kd_keg4'];
                        $kd_keg5 = $kegiatan['kd_keg5'];
    
                        foreach ($kegiatan['subKegiatan'] as $sub) {
                            $kd_subkeg1 = $sub['kd_subkeg1'];
                            $kd_subkeg2 = $sub['kd_subkeg2'];
                            $kd_subkeg3 = $sub['kd_subkeg3'];
                            $kd_subkeg4 = $sub['kd_subkeg4'];
                            $kd_subkeg5 = $sub['kd_subkeg5'];
                            $kd_subkeg6 = $sub['kd_subkeg6'];
    
                            foreach ($sub['rekening'] as $rek) {
                                $insertData[] = [
                                    'sp2d_id'      => $sp2d_id,
                                    'kd_urusan'    => $kd_urusan,
                                    'kd_bu1'       => $kd_bu1,
                                    'kd_bu2'       => $kd_bu2,
                                    'kd_prog1'     => $kd_prog1,
                                    'kd_prog2'     => $kd_prog2,
                                    'kd_prog3'     => $kd_prog3,
                                    'kd_keg1'      => $kd_keg1,
                                    'kd_keg2'      => $kd_keg2,
                                    'kd_keg3'      => $kd_keg3,
                                    'kd_keg4'      => $kd_keg4,
                                    'kd_keg5'      => $kd_keg5,
                                    'kd_subkeg1'   => $kd_subkeg1,
                                    'kd_subkeg2'   => $kd_subkeg2,
                                    'kd_subkeg3'   => $kd_subkeg3,
                                    'kd_subkeg4'   => $kd_subkeg4,
                                    'kd_subkeg5'   => $kd_subkeg5,
                                    'kd_subkeg6'   => $kd_subkeg6,
                                    'kd_rekening1' => $rek['kd_rekening1'],
                                    'kd_rekening2' => $rek['kd_rekening2'],
                                    'kd_rekening3' => $rek['kd_rekening3'],
                                    'kd_rekening4' => $rek['kd_rekening4'],
                                    'kd_rekening5' => $rek['kd_rekening5'],
                                    'kd_rekening6' => $rek['kd_rekening6'],
                                    'nilai'        => $rek['nilai'],
                                    'created_at'   => now(),
                                    'updated_at'   => now(),
                                ];
                            }
                        }
                    }
                }
            }
        }
    
        if (!empty($insertData)) {
            SP2DRekeningModel::insert($insertData); // batch insert lebih cepat
        }
    }

    private function saveSumberDana($sp2dId, $sumberDanaPayload)
    {
        if (empty($sumberDanaPayload) || !is_array($sumberDanaPayload)) {
            return;
        }

        foreach ($sumberDanaPayload as $item) {
            // Pastikan semua key tersedia
            $kd_ref1 = $item['kd_ref1'] ?? null;
            $kd_ref2 = $item['kd_ref2'] ?? null;
            $kd_ref3 = $item['kd_ref3'] ?? null;
            $kd_ref4 = $item['kd_ref4'] ?? null;
            $kd_ref5 = $item['kd_ref5'] ?? null;
            $kd_ref6 = $item['kd_ref6'] ?? null;

            // "nilai" dan "sisa" bisa berupa string atau number, jadi ubah ke float
            $nilai = isset($item['nilai']) ? floatval($item['nilai']) : 0;
            $sisa = isset($item['sisa']) ? floatval($item['sisa']) : 0;

            SP2DSumberDanaModel::create([
                'sp2d_id' => $sp2dId,
                'kd_ref1' => $kd_ref1,
                'kd_ref2' => $kd_ref2,
                'kd_ref3' => $kd_ref3,
                'kd_ref4' => $kd_ref4,
                'kd_ref5' => $kd_ref5,
                'kd_ref6' => $kd_ref6,
                'nilai' => $nilai,
            ]);
        }
    }


    /**
     * Detail SP2D
     */
    public function show($id)
    {
        $sp2d = SP2DModel::where('id_sp2d', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new SP2DResource($sp2d);
    }

    /**
     * Update SP2D
     */
    public function update(Request $request, $id)
    {
        $sp2d = SP2DModel::where('id_sp2d', $id)
                         ->whereNull('deleted_at')
                         ->first();
    
        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
    
        $validated = $request->validate([
            'tahun' => 'nullable|string|max:4',
            'id_user' => 'nullable|integer',
            'nama_user' => 'nullable|string|max:255',
            'id_operator' => 'nullable|integer',
            'nama_operator' => 'nullable|string|max:255',
            'kd_opd1' => 'nullable|string|max:5',
            'kd_opd2' => 'nullable|string|max:5',
            'kd_opd3' => 'nullable|string|max:5',
            'kd_opd4' => 'nullable|string|max:5',
            'kd_opd5' => 'nullable|string|max:5',
            'nama_file' => 'nullable|string|max:255',
            'nama_file_asli' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // max 10MB
            'file_tte' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'tanggal_upload' => 'nullable|date',
            'kode_file' => 'nullable|string|max:255',
            'diterima' => 'nullable|date',
            'ditolak' => 'nullable|date',
            'alasan_tolak' => 'nullable|string|max:500',
            'proses' => 'nullable|string|max:50',
            'supervisor_proses' => 'nullable|string|max:255',
            'urusan' => 'nullable|string',
            'kd_ref1' => 'nullable|string|max:5',
            'kd_ref2' => 'nullable|string|max:5',
            'kd_ref3' => 'nullable|string|max:5',
            'kd_ref4' => 'nullable|string|max:5',
            'kd_ref5' => 'nullable|string|max:5',
            'kd_ref6' => 'nullable|string|max:5',
            'no_spm' => 'nullable|string|max:255',
            'jenis_berkas' => 'nullable|string|max:255',
            'id_berkas' => 'nullable|string',
            'agreement' => 'nullable|string|max:255',
            'kd_belanja1' => 'nullable|string|max:5',
            'kd_belanja2' => 'nullable|string|max:5',
            'kd_belanja3' => 'nullable|string|max:5',
            'jenis_belanja' => 'nullable|string|max:255',
            'nilai_belanja' => 'nullable|string',
            'status_laporan' => 'nullable|string|max:255',
            'sp2d_rek' => 'nullable|string',
            'sumber_dana' => 'nullable|string'
        ]);

        $disk = Storage::disk('public');
        $folder = 'sp2d/' . date('Ymd');

        // handle nama_file_asli
        if ($request->hasFile('nama_file_asli')) {
            if ($sp2d->nama_file_asli && $disk->exists($sp2d->nama_file_asli)) {
                $disk->delete($sp2d->nama_file_asli);
            }
            $file = $request->file('nama_file_asli');
            $path = $file->store($folder, 'public');
            $validated['nama_file_asli'] = $path;
        } else {
            unset($validated['nama_file_asli']);
        }

        // handle file_tte
        if ($request->hasFile('file_tte')) {
            if ($sp2d->file_tte && $disk->exists($sp2d->file_tte)) {
                $disk->delete($sp2d->file_tte);
            }
            $fileTte = $request->file('file_tte');
            $pathTte = $fileTte->store($folder, 'public');
            $validated['file_tte'] = $pathTte;
        } else {
            unset($validated['file_tte']);
        }
    
        try {

            $sp2d->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => new SP2DResource($sp2d),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Soft delete SP2D
     */
    public function destroy($id)
    {
        $sp2d = SP2DModel::where('id_sp2d', $id)
                         ->whereNull('deleted_at')
                         ->first();

        if (!$sp2d) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $sp2d->deleted_at = now();
        $sp2d->save();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }

 /**
     * Menolak banyak SP2D sekaligus
     */
    public function terimaMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'supervisor_proses' => 'required|string'
        ]);
    
        $ids = $validated['ids'];
        $supervisor = $validated['supervisor_proses'];
    
        // Update semua berkas yang dipilih
        $updated = Sp2dModel::whereIn('id_sp2d', $ids)->update([
            'diterima' => now(),
            'proses' => 1,                     // status diterima
            'supervisor_proses' => $supervisor,
            'ditolak' => null,                 // pastikan ditolak kosong
            'alasan_tolak' => null,            // hapus alasan tolak
        ]);
    
        return response()->json([
            'success' => true,
            'message' => "Berhasil menerima $updated berkas SP2D.",
            'updated' => $updated
        ]);
    }
    

     /**
     * Menolak banyak SP2D sekaligus
     */
    public function tolakMulti(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'alasan' => 'required|string|max:500'
        ]);

        $ids = $validated['ids'];
        $alasan = $validated['alasan'];

        // Update semua berkas yang dipilih
        $updated = Sp2dModel::whereIn('id_sp2d', $ids)->update([
            'ditolak' => now(),
            'alasan_tolak' => $alasan,
            'proses' => 0,              // status proses kalau ditolak
            'supervisor_proses' => 0,   // sesuaikan jika butuh
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menolak $updated berkas SP2D.",
            'updated' => $updated
        ]);
    }

    public function downloadBerkas(int $id)
    {
        // Ambil data permohonan SPD berdasarkan id
        $permohonan = SP2DModel::findOrFail($id);

        $filePath = $permohonan->nama_file_asli;

        // Cek apakah file ada di disk public
        $disk = Storage::disk('public');
        if (!$disk->exists($filePath)) {
            abort(404, "File tidak ditemukan");
        }

        // Download file dengan nama asli
        return response()->download($disk->path($filePath), basename($filePath));
    }
}
