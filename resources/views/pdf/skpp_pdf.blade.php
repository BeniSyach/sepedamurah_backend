<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SKPP - {{ $skpp->nomor_surat }}</title>
    @php
        $fontRegularPath = public_path('assets/fonts/footlight/FootlightMTProRegular.otf');
        $fontBoldPath = public_path('assets/fonts/footlight/FootlightMTProBold.otf');
        $fontItalicPath = public_path('assets/fonts/footlight/FootlightMTProItalic.otf');

        $fontRegularBase64 = file_exists($fontRegularPath) ? base64_encode(file_get_contents($fontRegularPath)) : '';
        $fontBoldBase64 = file_exists($fontBoldPath) ? base64_encode(file_get_contents($fontBoldPath)) : '';
        $fontItalicBase64 = file_exists($fontItalicPath) ? base64_encode(file_get_contents($fontItalicPath)) : '';

        $logoPath = public_path('assets/img/logo_pemkab.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $isPensiun = strtolower($skpp->jenis_skpp ?? '') === 'pensiun';

        $formatDate = function($date) {
            if (empty($date) || $date === '-' || $date === '0000-00-00') return '-';
            try {
                return \Carbon\Carbon::parse($date)->format('d-m-Y');
            } catch (\Exception $e) {
                return $date;
            }
        };

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $formatDateIndo = function($date) use ($bulanIndo) {
            if (empty($date) || $date === '-' || $date === '0000-00-00') return '-';
            try {
                $c = \Carbon\Carbon::parse($date);
                $d = (int) $c->format('d');
                $m = (int) $c->format('m');
                $y = $c->format('Y');
                return $d . ' ' . ($bulanIndo[$m] ?? $c->format('F')) . ' ' . $y;
            } catch (\Exception $e) {
                return $date;
            }
        };

        $formatRupiah = function($val) {
            return 'Rp ' . number_format((float) ($val ?? 0), 0, ',', '.');
        };
    @endphp
    <style>
        @page {
            size: 215mm 330mm; /* F4 / Folio atau Legal */
            margin: 10mm 15mm 10mm 15mm;
        }

        @if(!empty($fontRegularBase64))
        @font-face {
            font-family: 'Footlight MT Pro';
            src: url('data:font/opentype;charset=utf-8;base64,{{ $fontRegularBase64 }}') format('opentype');
            font-weight: normal;
            font-style: normal;
        }
        @endif

        @if(!empty($fontBoldBase64))
        @font-face {
            font-family: 'Footlight MT Pro';
            src: url('data:font/opentype;charset=utf-8;base64,{{ $fontBoldBase64 }}') format('opentype');
            font-weight: bold;
            font-style: normal;
        }
        @endif

        @if(!empty($fontItalicBase64))
        @font-face {
            font-family: 'Footlight MT Pro';
            src: url('data:font/opentype;charset=utf-8;base64,{{ $fontItalicBase64 }}') format('opentype');
            font-weight: normal;
            font-style: italic;
        }
        @endif

        body {
            font-family: "Footlight MT Pro", "Times New Roman", Times, serif;
            font-size: 9.5pt;
            line-height: 1.25;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .underline { text-decoration: underline; }
        
        /* KOP SURAT DIPERBESAR SESUAI STANDAR RESMI */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo-cell {
            width: 90px;
            vertical-align: middle;
            text-align: center;
        }
        .header-spacer-cell {
            width: 90px;
        }
        .header-title-1 {
            font-size: 14.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .header-title-2 {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.2;
            margin-top: 2px;
        }
        .header-address {
            font-size: 9.5pt;
            line-height: 1.25;
            margin-top: 2px;
        }
        .double-line {
            border-top: 2.5px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-top: 5px;
            margin-bottom: 8px;
        }
        .title-section {
            text-align: center;
            margin-bottom: 8px;
        }
        .title-doc {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
        }
        .nomor-doc {
            font-size: 10pt;
            font-weight: bold;
        }
        table.pegawai-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        table.pegawai-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        
        .grid-gaji {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }
        .grid-gaji td {
            vertical-align: top;
        }
        table.table-subgaji {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        table.table-subgaji td {
            padding: 1px 2px;
        }
        .currency-symbol {
            width: 18px;
            text-align: left;
        }
        
        .table-bordered {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-top: 4px;
        }
        .table-bordered thead {
            display: table-header-group;
        }
        .table-bordered tr {
            page-break-inside: avoid;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000;
            padding: 2.5px 4px;
        }
        .table-bordered th {
            background-color: #f2f2f2;
            text-align: center;
        }
        
        .total-box {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 3px 0;
            margin: 5px 0;
            font-size: 9.5pt;
            font-weight: bold;
        }
        
        .ttd-section {
            width: 100%;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .ttd-box {
            float: right;
            width: 320px;
            text-align: center;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        .ttd-space {
            height: 85px; /* Ruang leluasa untuk barcode TTE */
        }
        .tembusan-section {
            font-size: 8pt;
            margin-top: 6px;
            clear: both;
        }
        .tembusan-list {
            margin: 2px 0 0 16px;
            padding: 0;
        }
        .tembusan-list li {
            margin-bottom: 1px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT DIPERBESAR DENGAN LOGO PEMKAB SERDANG BEDAGAI -->
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 82px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
                @elseif(file_exists(public_path('assets/img/logo_pemkab.png')))
                    <img src="{{ public_path('assets/img/logo_pemkab.png') }}" style="width: 82px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
                @endif
            </td>
            <td class="text-center" style="vertical-align: middle;">
                <div class="header-title-1 uppercase">PEMERINTAH KABUPATEN SERDANG BEDAGAI</div>
                <div class="header-title-2 uppercase">BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH</div>
                <div class="header-address">Jln. Negara No. 300 Kec. Sei Rampah Kab. Serdang Bedagai</div>
                <div class="header-address">Kode Pos 20995</div>
            </td>
            <td class="header-spacer-cell"></td>
        </tr>
    </table>

    <div class="double-line"></div>

    <!-- JUDUL SURAT -->
    <div class="title-section">
        <div class="title-doc">SURAT KETERANGAN PENGHENTIAN PEMBAYARAN (SKPP)</div>
        <div class="nomor-doc">NOMOR : {{ $skpp->nomor_surat }}</div>
    </div>

    <!-- PERNYATAAN -->
    <div style="font-size: 9.5pt;">
        @php
            $pejabatMenerangkan = $skpp->pejabat_menerangkan ?: 'KABID PERBENDAHARAAN, AKUNTANSI DAN PELAPORAN';
            $pejabatMenerangkan = str_ireplace('KABID PERBENDAHARAAN, AKUNTANSI DAN PELAPORAN DAERAH', 'KABID PERBENDAHARAAN, AKUNTANSI DAN PELAPORAN', $pejabatMenerangkan);
        @endphp
        <div class="font-bold uppercase">{{ $pejabatMenerangkan }}</div>
        <div>Menerangkan bahwa :</div>
        
        <table class="pegawai-table" style="margin-top: 3px;">
            <tr>
                <td style="width: 110px;">NIP</td>
                <td style="width: 10px;">:</td>
                <td>{{ $skpp->nip }}</td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td class="font-bold uppercase">{{ $skpp->nama }}</td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td>:</td>
                <td>{{ $formatDate($skpp->tgl_lahir) }}</td>
            </tr>
            <tr>
                <td>Golongan</td>
                <td>:</td>
                <td>{{ $skpp->golongan }}</td>
            </tr>
            <tr>
                <td>Unit Kerja</td>
                <td>:</td>
                <td class="uppercase">{{ $skpp->unit_kerja }}</td>
            </tr>
        </table>

        <!-- DASAR SK: MEMBEDAKAN PENSIUN DAN MUTASI -->
        <div style="margin-top: 5px;">
            @if($isPensiun)
                Sesuai dengan Surat Keputusan dari : <span class="font-bold uppercase">{{ $skpp->sk_dari }}</span><br>
                Nomor : {{ $skpp->nomor_sk }} &nbsp;&nbsp; Tanggal {{ $formatDate($skpp->tgl_sk) }} &nbsp;&nbsp; Terhitung mulai tanggal {{ $formatDate($skpp->tmt_sk) }}<br>
                Yang bersangkutan {{ $skpp->alasan_pensiun ?: 'diberhentikan dengan hormat sebagai Pegawai Negeri Sipil' }}<br>
                dengan Pensiun Pokok sebulan sebesar : <span class="font-bold">{{ $formatRupiah($skpp->pensiun_pokok ?? 0) }}</span>
            @else
                Sesuai dengan Surat Keputusan dari : <span class="font-bold uppercase">{{ $skpp->sk_dari }}</span><br>
                Nomor : {{ $skpp->nomor_sk }} &nbsp;&nbsp; Tanggal {{ $formatDate($skpp->tgl_sk) }} &nbsp;&nbsp; Terhitung mulai tanggal {{ $formatDate($skpp->tmt_sk) }}<br>
                Yang bersangkutan dimutasikan ke : <span class="font-bold uppercase">{{ $skpp->mutasi_ke }}</span>
            @endif
        </div>

        <div style="margin-top: 4px;">
            Gaji telah dibayarkan sampai dengan akhir bulan <span class="font-bold">{{ $skpp->gaji_terakhir_bulan }}</span> dengan perincian sebagai berikut :
        </div>
    </div>

    <!-- TABEL 2 KOLOM PENERIMAAN VS POTONGAN -->
    <table class="grid-gaji">
        <tr>
            <!-- A. PENERIMAAN -->
            <td style="width: 50%; padding-right: 10px;">
                <div class="font-bold" style="font-size: 9.5pt; margin-bottom: 2px;">A. Penerimaan</div>
                <table class="table-subgaji">
                    <tr><td>Gaji Pokok</td><td style="width:8px;">:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->gaji_pokok, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Istri/Suami</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_istri_suami, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Anak</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_anak, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunj. Perb. Penghasilan</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_perb_penghasilan, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Struktural</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_struktural, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Fungsional</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_fungsional, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Beras</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_beras, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Umum</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_umum, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Fungsi Khusus</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_fungsi_khusus, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Kemahalan Daerah</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_kemahalan_daerah, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Daerah Terpencil</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_daerah_terpencil, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Pajak</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_pajak, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan Kesehatan</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_kesehatan, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan JKK</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_jkk, 0, ',', '.') }}</td></tr>
                    <tr><td>Tunjangan JKM</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->tunj_jkm, 0, ',', '.') }}</td></tr>
                    <tr><td>Pembulatan</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pembulatan, 0, ',', '.') }}</td></tr>
                </table>
            </td>

            <!-- B. POTONGAN -->
            <td style="width: 50%; padding-left: 10px;">
                <div class="font-bold" style="font-size: 9.5pt; margin-bottom: 2px;">B. Potongan-Potongan</div>
                <table class="table-subgaji">
                    <tr><td>Pot IWP 1%</td><td style="width:8px;">:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_iwp_1, 0, ',', '.') }}</td></tr>
                    <tr><td>Pot IWP 8%</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_iwp_8, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan Askes</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_askes, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan Pajak</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_pajak, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan BULOG</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_bulog, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan Taperum</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_taperum, 0, ',', '.') }}</td></tr>
                    <tr><td>Iuran KORPRI</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->iuran_korpri, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan Sewa Rumah</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_sewa_rumah, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan Hutang</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_hutang, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan JKK</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_jkk, 0, ',', '.') }}</td></tr>
                    <tr><td>Potongan JKM</td><td>:</td><td class="currency-symbol">Rp</td><td class="text-right">{{ number_format($skpp->pot_jkm, 0, ',', '.') }}</td></tr>
                    <tr><td colspan="4" style="height: 12px;"></td></tr>
                    <tr style="border-top: 1px dashed #777;">
                        <td class="font-bold">Jumlah Potongan</td>
                        <td>:</td>
                        <td class="currency-symbol font-bold">Rp</td>
                        <td class="text-right font-bold">{{ number_format($skpp->jumlah_potongan, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TOTAL BAR -->
    <div class="total-box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; padding-right: 15px;">
                    <table style="width: 100%; font-size: 9.5pt;">
                        <tr>
                            <td class="font-bold">Jumlah Kotor</td>
                            <td style="width: 8px;">:</td>
                            <td style="width: 20px;" class="font-bold">Rp</td>
                            <td class="text-right font-bold">{{ number_format($skpp->jumlah_kotor, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; padding-left: 15px;">
                    <table style="width: 100%; font-size: 9.5pt;">
                        <tr>
                            <td class="font-bold">Jumlah Bersih</td>
                            <td style="width: 8px;">:</td>
                            <td style="width: 20px;" class="font-bold">Rp</td>
                            <td class="text-right font-bold">{{ number_format($skpp->jumlah_bersih, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- DAFTAR TANGGUNGAN -->
    <div style="margin-top: 5px;">
        <div style="font-size: 9pt; font-weight: bold;">Daftar keluarga yang menjadi tanggungan :</div>
        <table class="table-bordered">
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th>Nama</th>
                    <th style="width: 100px;">Tanggal Lahir</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if(is_array($skpp->tanggungan) && count($skpp->tanggungan) > 0)
                    @foreach($skpp->tanggungan as $idx => $t)
                        <tr>
                            <td class="text-center">{{ $t['no'] ?? ($idx + 1) }}</td>
                            <td class="uppercase">{{ $t['nama'] ?? '-' }}</td>
                            <td class="text-center">{{ $formatDate($t['tgl_lahir'] ?? '') }}</td>
                            <td>{{ $t['keterangan'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center" style="font-style: italic;">Tidak ada tanggungan keluarga</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- DATA HUTANG -->
    <div style="margin-top: 5px;">
        <div style="font-size: 9pt; font-weight: bold;">Data Hutang :</div>
        <table class="table-bordered">
            <thead>
                <tr>
                    <th style="width: 35%;">Jumlah Hutang</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $formatRupiah($skpp->jumlah_hutang ?? 0) }}</td>
                    <td>{{ $skpp->keterangan_hutang ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <div class="ttd-box">
            <div>{{ $skpp->tempat_penetapan ?? 'Sei Rampah' }}, {{ $formatDateIndo($skpp->tgl_penetapan) }}</div>
            @php
                $jabatanTtd = trim($skpp->jabatan_penandatangan ?? '');
                $isDefaultOrLegacy = empty($jabatanTtd) 
                    || str_contains(strtoupper($jabatanTtd), 'KABID PERBENDAHARAAN')
                    || str_contains(strtoupper($jabatanTtd), 'BADAN PENGELOLAAN KEUANGAN');
            @endphp
            @if($isDefaultOrLegacy)
                <div class="font-bold uppercase" style="margin-top: 2px; line-height: 1.2;">
                    <div>An. KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH</div>
                    <div>KABUPATEN SERDANG BEDAGAI</div>
                    <div>KABID PERBENDAHARAAN, AKUNTANSI DAN</div>
                    <div>PELAPORAN</div>
                </div>
            @else
                <div class="font-bold uppercase" style="margin-top: 2px; line-height: 1.2;">
                    {!! nl2br(e($jabatanTtd)) !!}
                </div>
            @endif
            <div class="ttd-space" style="text-align: center; line-height: 85px;">
                @if(!empty($skpp->barcode_base64))
                    <img src="{{ $skpp->barcode_base64 }}" style="width: 80px; height: 80px; vertical-align: middle;" />
                @else
                    <span style="font-size: 26pt; font-weight: bold; color: #222;">#</span>
                @endif
            </div>
            <div class="font-bold underline uppercase">{{ $skpp->nama_penandatangan ?? 'MUHAMMAD ALI LUBIS, SE' }}</div>
            <div>NIP. {{ $skpp->nip_penandatangan ?? '197801172008031002' }}</div>
        </div>
    </div>

    <!-- TEMBUSAN (SESUAI JENIS PENSIUN ATAU MUTASI) -->
    <div class="tembusan-section">
        <div class="font-bold">Tembusan SKPP ini disampaikan kepada :</div>
        <ol class="tembusan-list">
            @php
                $unitKerja = $skpp->unit_kerja ?? '';
                $isPendidikan = str_contains(strtolower($unitKerja), 'pendidikan');
                $tembusanList = $skpp->tembusan;
                if (is_string($tembusanList)) {
                    $tembusanList = json_decode($tembusanList, true);
                }
                $opdReplacement = !empty($unitKerja) 
                    ? (str_contains(strtolower($unitKerja), 'serdang bedagai') ? $unitKerja . ';' : $unitKerja . ' Kabupaten Serdang Bedagai;') 
                    : 'SKPD Asal / Terkait Kabupaten Serdang Bedagai;';
                $mutasiReplacement = !empty($skpp->mutasi_ke) ? $skpp->mutasi_ke . '.' : 'Pemerintah Daerah / Instansi Penerima.';
            @endphp
            @if(is_array($tembusanList) && count($tembusanList) > 0)
                @foreach($tembusanList as $item)
                    @php
                        $displayItem = $item;
                        if (!$isPendidikan && str_contains(strtolower($displayItem), 'dinas pendidikan')) {
                            $displayItem = str_ireplace('Dinas Pendidikan / SKPD Terkait;', $opdReplacement, $displayItem);
                            $displayItem = str_ireplace('Dinas Pendidikan / SKPD Asal.', $mutasiReplacement, $displayItem);
                            $displayItem = str_ireplace('Dinas Pendidikan Kabupaten Serdang Bedagai;', $opdReplacement, $displayItem);
                            $displayItem = str_ireplace('Dinas Pendidikan Kabupaten Serdang Bedagai.', $opdReplacement, $displayItem);
                        }
                    @endphp
                    <li>{{ $displayItem }}</li>
                @endforeach
            @else
                @if($isPensiun)
                    <li>Asli (Kepada yang bersangkutan untuk dilampirkan pada permintaan pensiun pertamanya);</li>
                    <li>PT. TASPEN (Persero) Cabang Utama Medan;</li>
                    <li>Kepala BPKAD Kabupaten Serdang Bedagai;</li>
                    <li>Bendahara {{ $opdReplacement }}</li>
                @else
                    <li>Asli (Kepada yang bersangkutan untuk dilampirkan pada Permintaan Gaji Pertamanya);</li>
                    <li>Badan Pengelola Keuangan dan Pendapatan Daerah Kabupaten Serdang Bedagai;</li>
                    <li>{{ $opdReplacement }}</li>
                    <li>{{ $mutasiReplacement }}</li>
                @endif
            @endif
        </ol>
        @if($skpp->catatan)
            <div style="margin-top: 3px;"><strong>Catatan :</strong> {{ $skpp->catatan }}</div>
        @endif
    </div>

    <!-- FOOTER RESMI HANYA MUNCUL KETIKA SUDAH TTE -->
    @if($skpp->status_tte === 'Sudah TTE')
    <div style="position: fixed; bottom: 8px; left: 0; right: 0; font-size: 7.5pt; color: #333; line-height: 1.35;">
        <div>- UU ITE No 11 Tahun 2008 Pasal 5 ayat 1</div>
        <div>"Informasi Elektronik dan/atau Dokumen Elektronik dan/atau hasil cetaknya merupakan alat bukti sah."</div>
        <div>- Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan BSrE.</div>
    </div>
    @endif

    <!-- SCRIPT MULTI-HALAMAN: RUNNING HEADER & PAGE NUMBERING (JIKA LEBIH DARI 1 LEMBAR) -->
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                if ($pageCount > 1) {
                    // Running header pada halaman 2 ke atas
                    if ($pageNumber > 1) {
                        $hFont = $fontMetrics->get_font("Times-Roman", "italic");
                        $canvas->text(42, 20, "SKPP Lanjutan | Halaman " . $pageNumber . " dari " . $pageCount, $hFont, 8, [0.4, 0.4, 0.4]);
                        $canvas->line(42, 30, $canvas->get_width() - 42, 30, [0.75, 0.75, 0.75], 0.5);
                    }

                    // Penomoran halaman di pojok kanan bawah
                    $text = "Halaman " . $pageNumber . " dari " . $pageCount;
                    $font = $fontMetrics->get_font("Times-Roman", "normal");
                    $size = 8;
                    $width = $fontMetrics->get_text_width($text, $font, $size);
                    $canvas->text($canvas->get_width() - $width - 42, $canvas->get_height() - 25, $text, $font, $size, [0.35, 0.35, 0.35]);
                }
            });
        }
    </script>

</body>
</html>
