<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Realisasi Anggaran Per Sumber Dana</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
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

        /* META FILTER (SKPD dll jika ada) */
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
            font-size: 8pt;
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
            font-size: 7.5pt;
            font-weight: normal;
            padding: 2px;
        }
        table.data-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .row-sumber {
            background-color: #E2E8F0;
            font-weight: bold;
        }
        .row-subtotal {
            background-color: #F8FAFC;
            font-weight: bold;
        }
        .row-grandtotal {
            background-color: #CBD5E1;
            font-weight: bold;
            font-size: 8.5pt;
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
            font-size: 8.5pt;
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
            height: 70px;
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

        $tglAwalFormatted = $formatDate($from ?? now()->startOfYear());
        $tglAkhirFormatted = $formatDate($tanggal ?? now());
        $tahunAnggaran = $tahun ?? (!empty($tanggal) ? \Carbon\Carbon::parse($tanggal)->year : date('Y'));
    @endphp

    <!-- KOP SURAT PEMKAB SERDANG BEDAGAI -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 58px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
                @elseif(file_exists(public_path('assets/img/logo_pemkab.png')))
                    <img src="{{ public_path('assets/img/logo_pemkab.png') }}" style="width: 58px; height: auto;" alt="Logo Pemkab Serdang Bedagai">
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
        <div class="report-title">LAPORAN REALISASI ANGGARAN (LRA)</div>
        <div class="report-subtitle">BERDASARKAN SUMBER DANA</div>
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

    <!-- TABEL DATA LRA SUMBER DANA -->
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 12%;">KODE</th>
                <th rowspan="2" style="width: 28%;">URAIAN</th>
                <th rowspan="2" style="width: 12%;">ANGGARAN</th>
                <th colspan="3" style="width: 36%;">REALISASI</th>
                <th rowspan="2" style="width: 12%;">LEBIH / (KURANG)</th>
            </tr>
            <tr>
                <th style="width: 12%;">s/d Periode Lalu</th>
                <th style="width: 12%;">Periode Ini</th>
                <th style="width: 12%;">Total s/d Periode Ini</th>
            </tr>
            <tr>
                <th class="col-num">1</th>
                <th class="col-num">2</th>
                <th class="col-num">3</th>
                <th class="col-num">4</th>
                <th class="col-num">5</th>
                <th class="col-num">6 = (4 + 5)</th>
                <th class="col-num">7 = (3 - 6)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalAnggaran = 0;
                $grandTotalLalu = 0;
                $grandTotalIni = 0;
                $grandTotalSampaiIni = 0;
                $grandTotalSisa = 0;
            @endphp

            @forelse ($data as $sd)
                @php
                    $sdAnggaran  = (float) ($sd['total_anggaran'] ?? 0);
                    $sdLalu      = (float) ($sd['total_lalu'] ?? 0);
                    $sdIni       = (float) ($sd['total_ini'] ?? 0);
                    $sdSampaiIni = (float) ($sd['total_sampai_ini'] ?? 0);
                    $sdSisa      = (float) ($sd['total_sisa'] ?? ($sdAnggaran - $sdSampaiIni));

                    $grandTotalAnggaran  += $sdAnggaran;
                    $grandTotalLalu      += $sdLalu;
                    $grandTotalIni       += $sdIni;
                    $grandTotalSampaiIni += $sdSampaiIni;
                    $grandTotalSisa      += $sdSisa;
                @endphp

                <!-- KELOMPOK SUMBER DANA -->
                <tr class="row-sumber">
                    <td colspan="7">
                        {{ $sd['kode'] }} - {{ $sd['nama'] }}
                    </td>
                </tr>

                <!-- RINCIAN BERJENJANG BELANJA S/D URUSAN / PROGRAM / KEGIATAN -->
                @if(!empty($sd['rows']) && count($sd['rows']) > 0)
                    @foreach ($sd['rows'] as $item)
                        @php
                            $itemAnggaran  = (float) ($item['anggaran'] ?? 0);
                            $itemLalu      = (float) ($item['realisasi_lalu'] ?? 0);
                            $itemIni       = (float) ($item['realisasi_ini'] ?? 0);
                            $itemTotal     = (float) ($item['realisasi_total'] ?? ($itemLalu + $itemIni));
                            $itemSisa      = (float) ($item['sisa'] ?? ($itemAnggaran - $itemTotal));
                            $level         = (int) ($item['level'] ?? 1);
                            $indentPx      = ($level - 1) * 12 + 4;
                            $isBold        = in_array($level, [1, 2]);
                            $rowBg         = $level === 1 ? 'background-color: #F8FAFC;' : '';
                        @endphp
                        <tr style="{{ $rowBg }}">
                            <td class="text-left {{ $isBold ? 'font-bold' : '' }}" style="padding-left: {{ $indentPx }}px;">
                                {{ $item['kode'] }}
                            </td>
                            <td class="{{ $isBold ? 'font-bold' : '' }}" style="padding-left: {{ $indentPx + 4 }}px;">
                                {{ $item['nama'] }}
                            </td>
                            <td class="text-right {{ $isBold ? 'font-bold' : '' }}">
                                {{ $formatRupiah($itemAnggaran) }}
                            </td>
                            <td class="text-right {{ $isBold ? 'font-bold' : '' }}">
                                {{ $formatRupiah($itemLalu) }}
                            </td>
                            <td class="text-right {{ $isBold ? 'font-bold' : '' }}">
                                {{ $formatRupiah($itemIni) }}
                            </td>
                            <td class="text-right {{ $isBold ? 'font-bold' : '' }}">
                                {{ $formatRupiah($itemTotal) }}
                            </td>
                            <td class="text-right {{ $isBold ? 'font-bold' : '' }}">
                                @if($itemSisa < 0)
                                    ({{ $formatRupiah(abs($itemSisa)) }})
                                @else
                                    {{ $formatRupiah($itemSisa) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center" style="font-style: italic; color: #666;">
                            Tidak ada rincian belanja untuk sumber dana ini.
                        </td>
                    </tr>
                @endif

                <!-- SUBTOTAL SUMBER DANA -->
                <tr class="row-subtotal">
                    <td colspan="2" class="text-right font-bold">
                        JUMLAH {{ $sd['nama'] }} :
                    </td>
                    <td class="text-right font-bold">
                        {{ $formatRupiah($sdAnggaran) }}
                    </td>
                    <td class="text-right font-bold">
                        {{ $formatRupiah($sdLalu) }}
                    </td>
                    <td class="text-right font-bold">
                        {{ $formatRupiah($sdIni) }}
                    </td>
                    <td class="text-right font-bold">
                        {{ $formatRupiah($sdSampaiIni) }}
                    </td>
                    <td class="text-right font-bold">
                        @if($sdSisa < 0)
                            ({{ $formatRupiah(abs($sdSisa)) }})
                        @else
                            {{ $formatRupiah($sdSisa) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; font-style: italic;">
                        Tidak ada data realisasi anggaran per sumber dana untuk periode yang dipilih.
                    </td>
                </tr>
            @endforelse

            <!-- GRAND TOTAL -->
            <tr class="row-grandtotal">
                <td colspan="2" class="text-right font-bold">
                    TOTAL KESELURUHAN :
                </td>
                <td class="text-right font-bold">
                    {{ $formatRupiah($grandTotalAnggaran) }}
                </td>
                <td class="text-right font-bold">
                    {{ $formatRupiah($grandTotalLalu) }}
                </td>
                <td class="text-right font-bold">
                    {{ $formatRupiah($grandTotalIni) }}
                </td>
                <td class="text-right font-bold">
                    {{ $formatRupiah($grandTotalSampaiIni) }}
                </td>
                <td class="text-right font-bold">
                    @if($grandTotalSisa < 0)
                        ({{ $formatRupiah(abs($grandTotalSisa)) }})
                    @else
                        {{ $formatRupiah($grandTotalSisa) }}
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="ttd-container">
        <table class="ttd-table">
            <tr>
                <td style="width: 60%;"></td>
                <td style="width: 40%; text-align: center;">
                    <div>Sei Rampah, {{ $tglAkhirFormatted }}</div>
                    <div class="font-bold uppercase" style="margin-top: 3px;">Kuasa Bendahara Umum Daerah</div>
                    <div class="ttd-space"></div>
                    <div class="font-bold uppercase" style="text-decoration: underline;">MUHAMMAD ALI LUBIS, SE</div>
                    <div>NIP. 197801172008031002</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
