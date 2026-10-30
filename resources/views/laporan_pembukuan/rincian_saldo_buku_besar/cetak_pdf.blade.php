<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rincian Akun Rekening Belanja (Buku Besar)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.25;
            color: #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* KOP SURAT */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-logo {
            width: 65px;
            vertical-align: middle;
            text-align: center;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
        }
        .kop-spacer {
            width: 65px;
        }
        .kop-title-1 {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .kop-title-2 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 1px;
        }
        .kop-address {
            font-size: 7.5pt;
            color: #333;
            margin-top: 2px;
        }
        .double-line {
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-top: 3px;
            margin-bottom: 8px;
        }

        /* JUDUL LAPORAN */
        .report-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .report-period {
            font-size: 8.5pt;
            margin-top: 3px;
        }

        /* META FILTER */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .meta-table td {
            padding: 1px 2px;
            border: none;
        }

        /* TABEL LAPORAN */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 15px;
        }
        table.data-table thead {
            display: table-header-group;
        }
        table.data-table tr {
            page-break-inside: avoid;
        }
        table.data-table th {
            border: 1px solid #000;
            background-color: #EAEAEA;
            text-align: center;
            padding: 4px 3px;
            font-weight: bold;
            vertical-align: middle;
        }
        table.data-table th.col-num {
            background-color: #F2F2F2;
            font-size: 7pt;
            font-weight: normal;
            padding: 2px;
        }
        table.data-table td {
            border: 1px solid #000;
            padding: 2.5px 3.5px;
            vertical-align: middle;
        }

        .row-rek-header {
            background-color: #D1D5DB;
            font-weight: bold;
        }
        .row-lalu {
            background-color: #F3F4F6;
            font-style: italic;
        }
        .row-subtotal {
            background-color: #E5E7EB;
            font-weight: bold;
        }
        .row-grandtotal {
            background-color: #9CA3AF;
            font-weight: bold;
            font-size: 8pt;
        }

        /* TANDA TANGAN */
        .ttd-container {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .ttd-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .ttd-box {
            width: 280px;
            text-align: center;
            float: right;
        }
        .ttd-space {
            height: 60px;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('assets/img/logo_pemkab.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $formatDate = function($date) {
            if (empty($date) || $date === '-' || $date === '0000-00-00') return '-';
            try {
                return \Carbon\Carbon::parse($date)->format('d-m-Y');
            } catch (\Exception $e) {
                return $date;
            }
        };

        $formatRupiah = function($val) {
            return 'Rp ' . number_format((float) ($val ?? 0), 2, ',', '.');
        };

        $formatPersen = function($val) {
            return number_format((float) ($val ?? 0), 2, ',', '.') . '%';
        };

        $tglAwalFormatted = $formatDate($from ?? now()->startOfYear());
        $tglAkhirFormatted = $formatDate($tanggal ?? now());
        $tahunAnggaran = $tahun ?? (!empty($tanggal) ? \Carbon\Carbon::parse($tanggal)->year : date('Y'));

        $grandAnggaran = 0;
        $grandLalu = 0;
        $grandIni = 0;
        $grandTotal = 0;
        $grandSisa = 0;
    @endphp

    <!-- KOP SURAT PEMKAB SERDANG BEDAGAI -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 55px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
                @elseif(file_exists(public_path('assets/img/logo_pemkab.png')))
                    <img src="{{ public_path('assets/img/logo_pemkab.png') }}" style="width: 55px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-title-1 uppercase">PEMERINTAH KABUPATEN SERDANG BEDAGAI</div>
                <div class="kop-title-2 uppercase">BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH</div>
                <div class="kop-address">Jln. Negara No. 300 Kec. Sei Rampah Kab. Serdang Bedagai, Kode Pos 20995</div>
            </td>
            <td class="kop-spacer"></td>
        </tr>
    </table>

    <div class="double-line"></div>

    <!-- JUDUL LAPORAN -->
    <div class="report-header">
        <div class="report-title">RINCIAN AKUN REKENING BELANJA</div>
        <div class="report-subtitle">BUKU BESAR PENGELUARAN / KARTU KENDALI REALISASI BELANJA</div>
        <div class="report-period">
            Periode : <strong>{{ $tglAwalFormatted }}</strong> s.d. <strong>{{ $tglAkhirFormatted }}</strong> &nbsp;|&nbsp; Tahun Anggaran : <strong>{{ $tahunAnggaran }}</strong>
        </div>
    </div>

    @if(!empty($meta) && count($meta) > 0)
        <table class="meta-table">
            @if(!empty($meta['urusan']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">Urusan</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['urusan'] }}</td>
                </tr>
            @endif
            @if(!empty($meta['bidang_urusan']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">Bidang Urusan</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['bidang_urusan'] }}</td>
                </tr>
            @endif
            @if(!empty($meta['skpd']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">SKPD</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['skpd'] }}</td>
                </tr>
            @endif
            @if(!empty($meta['program']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">Program</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['program'] }}</td>
                </tr>
            @endif
            @if(!empty($meta['kegiatan']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">Kegiatan</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['kegiatan'] }}</td>
                </tr>
            @endif
            @if(!empty($meta['subkegiatan']))
                <tr>
                    <td style="width: 100px; font-weight: bold;">Sub Kegiatan</td>
                    <td style="width: 10px;">:</td>
                    <td>{{ $meta['subkegiatan'] }}</td>
                </tr>
            @endif
        </table>
    @elseif(!empty($skpd) && (!empty($skpd['nm_opd']) || !empty($skpd['kd_opd1'])))
        <table class="meta-table">
            <tr>
                <td style="width: 100px; font-weight: bold;">SKPD</td>
                <td style="width: 10px;">:</td>
                <td>
                    @if(!empty($skpd['kd_opd1']))
                        {{ implode('.', array_filter([$skpd['kd_opd1'] ?? '', $skpd['kd_opd2'] ?? '', $skpd['kd_opd3'] ?? '', $skpd['kd_opd4'] ?? '', $skpd['kd_opd5'] ?? ''])) }} - 
                    @endif
                    {{ $skpd['nm_opd'] ?? 'Semua SKPD' }}
                </td>
            </tr>
        </table>
    @endif

    <!-- TABEL DATA BUKU BESAR -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 8%;">TANGGAL</th>
                <th style="width: 25%;">NO. BUKTI / SPM</th>
                <th style="width: 6%;">JENIS</th>
                <th style="width: 19%;">URAIAN</th>
                <th style="width: 13%;">PENGELUARAN (RP)</th>
                <th style="width: 13%;">KUMULATIF REALISASI</th>
                <th style="width: 12%;">SISA ANGGARAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $r)
                @php
                    $grandAnggaran += (float)$r['anggaran'];
                    $grandLalu     += (float)$r['realisasi_lalu'];
                    $grandIni      += (float)$r['realisasi_ini'];
                    $grandTotal    += (float)$r['total_realisasi'];
                    $grandSisa     += (float)$r['sisa'];
                @endphp

                <!-- HEADER REKENING -->
                <tr class="row-rek-header">
                    <td colspan="5">
                        REKENING : <strong>{{ $r['kode'] }}</strong> - {{ $r['nama'] }}
                    </td>
                    <td colspan="3" class="text-right">
                        ANGGARAN: <strong>{{ $formatRupiah($r['anggaran']) }}</strong>
                    </td>
                </tr>

                <!-- SALDO AWAL / LALU -->
                <tr class="row-lalu">
                    <td class="text-center">-</td>
                    <td class="text-center">{{ $tglAwalFormatted }}</td>
                    <td colspan="3">Realisasi s/d Periode Lalu</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ $formatRupiah($r['realisasi_lalu']) }}</td>
                    <td class="text-right">{{ $formatRupiah($r['anggaran'] - $r['realisasi_lalu']) }}</td>
                </tr>

                <!-- DETAIL TRANSAKSI -->
                @forelse($r['transaksi'] as $idx => $tx)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $formatDate($tx['tanggal']) }}</td>
                        <td>{{ $tx['no_spm'] }}</td>
                        <td class="text-center">{{ $tx['jenis'] }}</td>
                        <td>{{ $tx['uraian'] }}</td>
                        <td class="text-right">{{ $formatRupiah($tx['nilai']) }}</td>
                        <td class="text-right">{{ $formatRupiah($tx['kumulatif']) }}</td>
                        <td class="text-right">{{ $formatRupiah($tx['sisa']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="font-style: italic; color: #666; padding: 4px;">
                            Tidak ada transaksi pada periode {{ $tglAwalFormatted }} s.d. {{ $tglAkhirFormatted }}
                        </td>
                    </tr>
                @endforelse

                <!-- SUBTOTAL REKENING -->
                <tr class="row-subtotal">
                    <td colspan="5" class="text-center font-bold">TOTAL REALISASI REKENING ({{ $r['kode'] }})</td>
                    <td class="text-right font-bold">{{ $formatRupiah($r['realisasi_ini']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($r['total_realisasi']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($r['sisa']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; font-style: italic;">
                        Tidak ada data transaksi rekening belanja yang sesuai dengan kriteria filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
            <tfoot>
                <tr class="row-grandtotal">
                    <td colspan="5" class="text-center uppercase font-bold" style="padding: 5px;">TOTAL KESELURUHAN (SEMUA REKENING)</td>
                    <td class="text-right font-bold">{{ $formatRupiah($grandIni) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($grandTotal) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($grandSisa) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- TANDA TANGAN PEJABAT BPKAD -->
    <div class="ttd-container">
        <table class="ttd-table">
            <tr>
                <td style="width: 60%;"></td>
                <td style="width: 40%;">
                    <div class="ttd-box">
                        <div>Sei Rampah, {{ \Carbon\Carbon::parse($tanggal ?? now())->isoFormat('D MMMM Y') }}</div>
                        <div class="font-bold" style="margin-top: 4px;">Kepala Badan Pengelolaan Keuangan<br>dan Aset Daerah</div>
                        <div class="ttd-space"></div>
                        <div class="font-bold" style="text-decoration: underline;">RUSMIANI PURBA, SP, M.Si</div>
                        <div>Pembina Utama Muda (IV/c)</div>
                        <div>NIP. 19721112 199803 2 004</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
