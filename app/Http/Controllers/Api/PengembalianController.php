<?php

namespace App\Http\Controllers\Api;

use App\Exports\RekapPengembalianExport;
use App\Http\Controllers\Controller;
use App\Models\PengembalianModel;
use Illuminate\Http\Request;
use App\Http\Resources\PengembalianResource;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PengembalianController extends Controller
{
    /**
     * Daftar data pengembalian dengan pagination dan search.
     */
    public function index(Request $request)
    {
        $query = PengembalianModel::query();

        if ($search = $request->get('search')) {
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_sts', 'like', "%{$search}%");
        }

        $data = $query->orderBy('tgl_rekam', 'desc')
                      ->paginate($request->get('per_page', 10));
        
          // Attach skpd secara manual (karena tidak bisa eager load)
        $data->getCollection()->transform(function ($item) {
            $skpd = $item->skpd(); // panggil accessor manual
            $item->setRelation('skpd', $skpd); // daftarkan ke relasi Eloquent
            return $item;
        });

        return PengembalianResource::collection($data);
    }

    /**
     * Simpan data baru.
     */
    public function store(Request $request)
    {
        // 1️⃣ Validasi
        $validator = Validator::make($request->all(), [
            'nik' => 'required|numeric|digits:16',
            'name' => 'required|string',
            'alamat' => 'required|string',
            'tahun' => 'required|string|max:4',
            'rekening' => 'required|string',
            'jumlah' => 'required',
            'skpd' => 'required|string',
            'keterangan' => 'required|string',
        ], [
            'nik.required' => 'nik tidak boleh kosong',
            'nik.digits' => 'nik harus 16 digit',
            'nik.numeric' => 'nik harus berupa angka',
            'name.required' => 'nama lengkap harus diisi',
            'alamat.required' => 'alamat harus diisi',
            'tahun.required' => 'tahun harus diisi',
            'rekening.required' => 'rekening harus diisi',
            'jumlah.required' => 'nilai pengembalian harus diisi',
            'skpd.required' => 'skpd harus dipilih',
            'keterangan.required' => 'keterangan harus diisi',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'error',
                'nik' => $validator->errors()->first('nik'),
                'name' => $validator->errors()->first('name'),
                'alamat' => $validator->errors()->first('alamat'),
                'tahun' => $validator->errors()->first('tahun'),
                'rekening' => $validator->errors()->first('rekening'),
                'jumlah' => $validator->errors()->first('jumlah'),
                'skpd' => $validator->errors()->first('opd'),
                'keterangan' => $validator->errors()->first('keterangan'),
            ],400);
        }
    
        try {
            // 2️⃣ Proses data input
            $nik = intval($request->nik);
            $name = strtoupper($request->name);
            $alamat = strtoupper($request->alamat);
            $tahun = $request->tahun;
    
            // hilangkan karakter non-numerik di jumlah
            $jumlah = preg_replace('/[^0-9]/', '', $request->jumlah);
    
            $rekening = $request->rekening;
            $keterangan = $request->keterangan;
            $kdrek = explode('.', $rekening);
    
            $skpd = $request->skpd;
            $kdskpd = explode('.', $skpd);
    
            // 3️⃣ Siapkan data insert
            $datainsert = [
                'nik' => $nik,
                'nama' => $name,
                'alamat' => $alamat,
                'tahun' => $tahun,
                'kd_rek1' => $kdrek[0] ?? null,
                'kd_rek2' => $kdrek[1] ?? null,
                'kd_rek3' => $kdrek[2] ?? null,
                'kd_rek4' => $kdrek[3] ?? null,
                'kd_rek5' => $kdrek[4] ?? null,
                'kd_rek6' => $kdrek[5] ?? null,
                'nm_rekening' => $kdrek[6] ?? '',
                'kd_opd1' => $kdskpd[0] ?? null,
                'kd_opd2' => $kdskpd[1] ?? null,
                'kd_opd3' => $kdskpd[2] ?? null,
                'kd_opd4' => $kdskpd[3] ?? null,
                'kd_opd5' => $kdskpd[4] ?? null,
                'jml_pengembalian' => $jumlah,
                'keterangan' => $keterangan,
            ];
    
            // 4️⃣ Simpan ke database
            $pengembalian = PengembalianModel::create($datainsert);
            $pengembalian = PengembalianModel::latest('created_at')->first();
    
            if ($pengembalian) {
                // 5️⃣ Buat data sukses
                $terbilang = app(Terbilang::class)->kalimat($jumlah);
                $rekening_nama = $datainsert['nm_rekening'];
                $tanggal = now()->locale('id')->translatedFormat('d F Y');
    
                $msg = [
                    'sukses' => 'berhasil! pengembalian dana kamu berhasil disimpan. terimakasih',
                    'nama' => $name,
                    'nik' => $nik,
                    'penyetor' => $name,
                    'alamat' => $alamat,
                    'rekening' => $rekening_nama,
                    'keterangan' => $keterangan,
                    'jumlah' => 'rp. ' . number_format($jumlah, 0, ',', '.'),
                    'terbilang' => $terbilang,
                    'no_billing' => $pengembalian->no_sts ?? 'belum ada nomor billing',
                    'tanggal' => $tanggal,
                    'link' => route('pengembalian.print', [
                        'nama' => $name,
                        'penyetor' => $name,
                        'alamat' => $alamat,
                        'rekening' => $rekening_nama,
                        'keterangan' => $keterangan,
                        'jumlah' => $jumlah,
                        'no_billing' => $pengembalian->no_sts ?? '',
                        'nik' => $nik,
                    ]),
                    'link_pdf' => route('pengembalian.print', [
                        'nama' => $name,
                        'penyetor' => $name,
                        'alamat' => $alamat,
                        'rekening' => $rekening_nama,
                        'keterangan' => $keterangan,
                        'jumlah' => $jumlah,
                        'no_billing' => $pengembalian->no_sts ?? '',
                        'nik' => $nik,
                    ]),
                ];
    
                return response()->json($msg);
            }
    
            return response()->json(['error' => 'gagal disimpan'], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'terjadi kesalahan',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Tampilkan detail 
     */
    public function show($id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return new PengembalianResource($pengembalian);
    }

    /**
     * Update data 
     */
    public function update(Request $request, $id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'NIK' => 'required|string|max:50',
            'NAMA' => 'required|string|max:255',
            'ALAMAT' => 'nullable|string|max:255',
            'TAHUN' => 'required|string|max:4',
            'KD_REK1' => 'nullable|string|max:2',
            'KD_REK2' => 'nullable|string|max:2',
            'KD_REK3' => 'nullable|string|max:2',
            'KD_REK4' => 'nullable|string|max:2',
            'KD_REK5' => 'nullable|string|max:2',
            'KD_REK6' => 'nullable|string|max:4',
            'NM_REKENING' => 'required|string|max:100',
            'KETERANGAN' => 'nullable|string|max:255',
            'KD_OPD1' => 'nullable|string|max:2',
            'KD_OPD2' => 'nullable|string|max:2',
            'KD_OPD3' => 'nullable|string|max:2',
            'KD_OPD4' => 'nullable|string|max:2',
            'KD_OPD5' => 'nullable|string|max:2',
            'JML_PENGEMBALIAN' => 'nullable|numeric',
            'TGL_REKAM' => 'nullable|date',
            'JML_YG_DISETOR' => 'nullable|numeric',
            'TGL_SETOR' => 'nullable|date',
            'NIP_PEREKAM' => 'nullable|string|max:50',
            'KODE_PENGESAHAN' => 'nullable|string|max:10',
            'KODE_CABANG' => 'nullable|string|max:10',
            'NAMA_CHANNEL' => 'nullable|string|max:50',
            'STATUS_PEMBAYARAN_PAJAK' => 'nullable|string|max:50',
        ]);

        $pengembalian->update($validated);

        return new PengembalianResource($pengembalian);
    }

    /**
     * Soft delete data 
     */
    public function destroy($id)
    {
        $pengembalian = PengembalianModel::find($id);

        if (!$pengembalian) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $pengembalian->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus (soft delete)',
        ]);
    }

    public function tabelPrint(Request $request)
    {
        $nik         = $request->query('nik');
        $nama        = $request->query('nama');
        $penyetor    = $request->query('penyetor');
        $alamat      = $request->query('alamat');
        $rekening    = $request->query('rekening');
        $keterangan  = $request->query('keterangan');
        $jumlah      = (int) $request->query('jumlah');
        $no_billing  = $request->query('no_billing');

        // gunakan helper Terbilang dan format tanggal
        $terbilang = app(Terbilang::class)->kalimat($jumlah);
        $tanggal   = app(Terbilang::class)->tglIndo(date('Y-m-d'));

        $data = [
            'title'      => 'Pengembalian Dana',
            'nama'       => $nama,
            'nik'        => $nik,
            'alamat'     => $alamat,
            'penyetor'   => $penyetor,
            'rekening'   => $rekening,
            'keterangan' => $keterangan,
            'jumlah'     => 'Rp. ' . number_format($jumlah, 0, ',', '.'),
            'terbilang'  => $terbilang,
            'tanggal'    => $tanggal,
            'no_billing' => $no_billing,
        ];

        // render ke Blade lalu jadikan PDF
        $pdf = Pdf::loadView('pengembalian.cetak_pdf', $data)
            ->setPaper('a4', 'portrait');

        // Tampilkan di browser
        $filename = 'pengembalian_dana_' . date('Ymd_His') . '.pdf';
        return $pdf->stream($filename);
    }

    public function rekapPengembalianPDF(Request $request)
    {
        // Ambil filter dari request
        $tahun = $request->query('tahun', date('Y'));
        $bulan = $request->query('bulan', date('m'));
        $skpd  = $request->query('skpd', null);
    
        $start = $request->query('start_date', "$tahun-$bulan-01");
        $end   = $request->query('end_date', date('Y-m-t', strtotime($start))); // akhir bulan
    
        // Query utama (gunakan nama parameter yang aman untuk Oracle)
        $query = "
            SELECT 
                a.NIK, a.NAMA, a.ALAMAT, b.NM_OPD, 
                a.NM_REKENING AS KET_PENGEMBALIAN,
                a.JML_PENGEMBALIAN, a.TGL_SETOR, 
                a.JML_YG_DISETOR, a.KETERANGAN AS KET_TAMBAHAN, 
                a.TGL_REKAM,
                CASE
                    WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR <= 0 THEN 'SDH BAYAR'
                    WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR = a.JML_PENGEMBALIAN THEN 'BLM BAYAR'
                    ELSE 'KRG BAYAR'
                END AS KETERANGAN
            FROM DATA_PENGEMBALIAN a
            INNER JOIN REF_OPD b
                ON a.KD_OPD1 = b.KD_OPD1
                AND a.KD_OPD2 = b.KD_OPD2
                AND a.KD_OPD3 = b.KD_OPD3
                AND a.KD_OPD4 = b.KD_OPD4
                AND a.KD_OPD5 = b.KD_OPD5
            WHERE a.TGL_REKAM >= TO_DATE(:p_start, 'YYYY-MM-DD') 
            AND a.TGL_REKAM <= TO_DATE(:p_end, 'YYYY-MM-DD')
        ";
    
        // Binding awal
        $bindings = [
            'p_start' => $start,
            'p_end'   => $end,
        ];
    
        // Filter OPD jika ada
        if ($skpd) {
            $kode_opd = array_map(fn($part) => $part === '' ? null : $part, explode('-', $skpd));
    
            $query .= " 
                AND a.KD_OPD1 = :p_KD_OPD1 
                AND a.KD_OPD2 = :p_KD_OPD2 
                AND a.KD_OPD3 = :p_KD_OPD3 
                AND a.KD_OPD4 = :p_KD_OPD4 
                AND a.KD_OPD5 = :p_KD_OPD5
            ";
    
            $bindings = array_merge($bindings, [
                'p_KD_OPD1' => $kode_opd[0] ?? null,
                'p_KD_OPD2' => $kode_opd[1] ?? null,
                'p_KD_OPD3' => $kode_opd[2] ?? null,
                'p_KD_OPD4' => $kode_opd[3] ?? null,
                'p_KD_OPD5' => $kode_opd[4] ?? null,
            ]);
        }
    
        // Eksekusi query
        $data_pengembalian = DB::select($query, $bindings);
    
        // Generate PDF
        $pdf = Pdf::loadView('pengembalian_admin.cetak_pdf', [
            'data_pengembalian' => $data_pengembalian,
            'periode' => [$start, $end],
        ])->setPaper('a4', 'landscape');
    
        return $pdf->stream('rekap_pengembalian_' . date('Ymd_His') . '.pdf');
    }
    

    public function rekapPengembalianExcel(Request $request)
    {
        // Ambil filter dari request
        $tahun = $request->query('tahun', date('Y'));
        $bulan = $request->query('bulan', date('m'));
        $skpd  = $request->query('skpd', null);
    
        $start = $request->query('start_date', "$tahun-$bulan-01");
        $end   = $request->query('end_date', date('Y-m-t', strtotime($start))); // akhir bulan
    
        // Query utama (gunakan nama parameter yang aman untuk Oracle)
        $query = "
            SELECT 
                a.NIK, a.NAMA, a.ALAMAT, b.NM_OPD, 
                a.NM_REKENING AS KET_PENGEMBALIAN,
                a.JML_PENGEMBALIAN, a.TGL_SETOR, 
                a.JML_YG_DISETOR, a.KETERANGAN AS KET_TAMBAHAN, 
                a.TGL_REKAM,
                CASE
                    WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR <= 0 THEN 'SDH BAYAR'
                    WHEN a.JML_PENGEMBALIAN - a.JML_YG_DISETOR = a.JML_PENGEMBALIAN THEN 'BLM BAYAR'
                    ELSE 'KRG BAYAR'
                END AS KETERANGAN
            FROM DATA_PENGEMBALIAN a
            INNER JOIN REF_OPD b
                ON a.KD_OPD1 = b.KD_OPD1
                AND a.KD_OPD2 = b.KD_OPD2
                AND a.KD_OPD3 = b.KD_OPD3
                AND a.KD_OPD4 = b.KD_OPD4
                AND a.KD_OPD5 = b.KD_OPD5
            WHERE a.TGL_REKAM >= TO_DATE(:p_start, 'YYYY-MM-DD') 
            AND a.TGL_REKAM <= TO_DATE(:p_end, 'YYYY-MM-DD')
        ";
    
        // Binding awal
        $bindings = [
            'p_start' => $start,
            'p_end'   => $end,
        ];
    
        // Filter OPD jika ada
        if ($skpd) {
            $kode_opd = array_map(fn($part) => $part === '' ? null : $part, explode('-', $skpd));
    
            $query .= " 
                AND a.KD_OPD1 = :p_KD_OPD1 
                AND a.KD_OPD2 = :p_KD_OPD2 
                AND a.KD_OPD3 = :p_KD_OPD3 
                AND a.KD_OPD4 = :p_KD_OPD4 
                AND a.KD_OPD5 = :p_KD_OPD5
            ";
    
            $bindings = array_merge($bindings, [
                'p_KD_OPD1' => $kode_opd[0] ?? null,
                'p_KD_OPD2' => $kode_opd[1] ?? null,
                'p_KD_OPD3' => $kode_opd[2] ?? null,
                'p_KD_OPD4' => $kode_opd[3] ?? null,
                'p_KD_OPD5' => $kode_opd[4] ?? null,
            ]);
        }
    
        // Eksekusi query
        $data_pengembalian = DB::select($query, $bindings);
    
        // Export ke Excel
        return Excel::download(
            new RekapPengembalianExport($data_pengembalian, $tahun, $bulan),
            "rekap_pengembalian_{$tahun}_{$bulan}.xlsx"
        );
    }
}
