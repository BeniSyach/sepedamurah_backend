<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Realisasi Anggaran Pendapatan dan Belanja</title>
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

        .row-akun {
            background-color: #CBD5E1;
            font-weight: bold;
            font-size: 8pt;
        }
        .row-kelompok {
            background-color: #E2E8F0;
            font-weight: bold;
        }
        .row-subtotal {
            background-color: #F1F5F9;
            font-weight: bold;
        }
        .row-highlight {
            background-color: #94A3B8;
            font-weight: bold;
            font-size: 8pt;
        }
        .row-silpa {
            background-color: #64748B;
            color: #FFF;
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
            $isNeg = (float)$val < 0;
            $res = 'Rp ' . number_format(abs((float) ($val ?? 0)), 2, ',', '.');
            return $isNeg ? "($res)" : $res;
        };

        $formatPersen = function($val) {
            return number_format((float) ($val ?? 0), 2, ',', '.') . '%';
        };

        $tglAwalFormatted = $formatDate($from ?? now()->startOfYear());
        $tglAkhirFormatted = $formatDate($tanggal ?? now());
        $tahunAnggaran = $tahun ?? (!empty($tanggal) ? \Carbon\Carbon::parse($tanggal)->year : date('Y'));

        // Hitung Total Pendapatan
        $totPendapatanAnggaran = 0;
        foreach($pendapatan as $p) {
            foreach($p['items'] as $i) {
                $totPendapatanAnggaran += (float)$i['anggaran'];
            }
        }

        // Hitung Total Belanja
        $totBelanjaAnggaran = 0;
        $totBelanjaLalu     = 0;
        $totBelanjaIni      = 0;
        $totBelanjaSampai   = 0;
        $totBelanjaSisa     = 0;
        foreach($belanja as $b) {
            $totBelanjaAnggaran += (float)$b['total_anggaran'];
            $totBelanjaLalu     += (float)$b['total_lalu'];
            $totBelanjaIni      += (float)$b['total_ini'];
            $totBelanjaSampai   += (float)$b['total_sampai_ini'];
            $totBelanjaSisa     += (float)$b['total_sisa'];
        }

        // Surplus / Defisit
        $surplusAnggaran = $totPendapatanAnggaran - $totBelanjaAnggaran;
        $surplusLalu     = 0 - $totBelanjaLalu;
        $surplusIni      = 0 - $totBelanjaIni;
        $surplusSampai   = 0 - $totBelanjaSampai;
        $surplusSisa     = $totPendapatanAnggaran - $surplusSampai;

        // Pembiayaan
        $totPembiayaanAnggaran = 0;
        foreach($pembiayaan as $pm) {
            foreach($pm['items'] as $i) {
                $totPembiayaanAnggaran += (float)$i['anggaran'];
            }
        }

        // SILPA
        $silpaAnggaran = $surplusAnggaran + $totPembiayaanAnggaran;
        $silpaSampai   = $surplusSampai + 0;
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
        <div class="report-title">LAPORAN REALISASI ANGGARAN (LRA)</div>
        <div class="report-subtitle">RINGKASAN REALISASI PENDAPATAN DAN BELANJA DAERAH</div>
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

    <!-- TABEL DATA LRA PENDAPATAN & BELANJA -->
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 12%;">KODE</th>
                <th rowspan="2" style="width: 26%;">URAIAN</th>
                <th rowspan="2" style="width: 11%;">ANGGARAN</th>
                <th colspan="3" style="width: 31%;">REALISASI</th>
                <th rowspan="2" style="width: 11%;">LEBIH / (KURANG)</th>
                <th rowspan="2" style="width: 9%;">%</th>
            </tr>
            <tr>
                <th style="width: 10%;">s/d Periode Lalu</th>
                <th style="width: 10%;">Periode Ini</th>
                <th style="width: 11%;">Total s/d Periode Ini</th>
            </tr>
            <tr>
                <th class="col-num">1</th>
                <th class="col-num">2</th>
                <th class="col-num">3</th>
                <th class="col-num">4</th>
                <th class="col-num">5</th>
                <th class="col-num">6 = (4 + 5)</th>
                <th class="col-num">7 = (3 - 6)</th>
                <th class="col-num">8 = (6 / 3)</th>
            </tr>
        </thead>
        <tbody>
            <!-- ===================== 4. PENDAPATAN DAERAH ===================== -->
            <tr class="row-akun">
                <td class="font-bold">4</td>
                <td class="font-bold">PENDAPATAN DAERAH</td>
                <td class="text-right font-bold">{{ $formatRupiah($totPendapatanAnggaran) }}</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">{{ $formatRupiah($totPendapatanAnggaran) }}</td>
                <td class="text-center font-bold">0.00%</td>
            </tr>

            @foreach($pendapatan as $p)
                @php
                    $subP = collect($p['items'])->sum('anggaran');
                @endphp
                <tr class="row-kelompok">
                    <td class="font-bold" style="padding-left: 8px;">{{ $p['kode'] }}</td>
                    <td class="font-bold" style="padding-left: 8px;">{{ $p['nama'] }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($subP) }}</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">{{ $formatRupiah($subP) }}</td>
                    <td class="text-center font-bold">0.00%</td>
                </tr>

                @foreach($p['items'] as $i)
                    <tr>
                        <td style="padding-left: 16px;">{{ $i['kode'] }}</td>
                        <td style="padding-left: 16px;">{{ $i['nama'] }}</td>
                        <td class="text-right">{{ $formatRupiah($i['anggaran']) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ $formatRupiah($i['anggaran']) }}</td>
                        <td class="text-center">0.00%</td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="row-subtotal">
                <td colspan="2" class="text-center font-bold uppercase">JUMLAH PENDAPATAN</td>
                <td class="text-right font-bold">{{ $formatRupiah($totPendapatanAnggaran) }}</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">{{ $formatRupiah($totPendapatanAnggaran) }}</td>
                <td class="text-center font-bold">0.00%</td>
            </tr>

            <!-- ===================== 5. BELANJA DAERAH ===================== -->
            <tr class="row-akun">
                <td class="font-bold">5</td>
                <td class="font-bold">BELANJA DAERAH</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaAnggaran) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaLalu) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaIni) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaSampai) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaSisa) }}</td>
                <td class="text-center font-bold">{{ $formatPersen($totBelanjaAnggaran > 0 ? ($totBelanjaSampai / $totBelanjaAnggaran) * 100 : 0) }}</td>
            </tr>

            @foreach($belanja as $b)
                <tr class="row-kelompok">
                    <td class="font-bold" style="padding-left: 8px;">{{ $b['kode'] }}</td>
                    <td class="font-bold" style="padding-left: 8px;">{{ $b['nama'] }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($b['total_anggaran']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($b['total_lalu']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($b['total_ini']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($b['total_sampai_ini']) }}</td>
                    <td class="text-right font-bold">{{ $formatRupiah($b['total_sisa']) }}</td>
                    <td class="text-center font-bold">{{ $formatPersen($b['persen']) }}</td>
                </tr>

                @foreach($b['items'] as $i)
                    <tr>
                        <td style="padding-left: 16px;">{{ $i['kode'] }}</td>
                        <td style="padding-left: 16px;">{{ $i['nama'] }}</td>
                        <td class="text-right">{{ $formatRupiah($i['anggaran']) }}</td>
                        <td class="text-right">{{ $formatRupiah($i['realisasi_lalu']) }}</td>
                        <td class="text-right">{{ $formatRupiah($i['realisasi_ini']) }}</td>
                        <td class="text-right">{{ $formatRupiah($i['realisasi_total']) }}</td>
                        <td class="text-right">{{ $formatRupiah($i['sisa']) }}</td>
                        <td class="text-center">{{ $formatPersen($i['persen']) }}</td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="row-subtotal">
                <td colspan="2" class="text-center font-bold uppercase">JUMLAH BELANJA</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaAnggaran) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaLalu) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaIni) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaSampai) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($totBelanjaSisa) }}</td>
                <td class="text-center font-bold">{{ $formatPersen($totBelanjaAnggaran > 0 ? ($totBelanjaSampai / $totBelanjaAnggaran) * 100 : 0) }}</td>
            </tr>

            <!-- ===================== SURPLUS / (DEFISIT) ===================== -->
            <tr class="row-highlight">
                <td colspan="2" class="text-center font-bold uppercase">SURPLUS / (DEFISIT)</td>
                <td class="text-right font-bold">{{ $formatRupiah($surplusAnggaran) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($surplusLalu) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($surplusIni) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($surplusSampai) }}</td>
                <td class="text-right font-bold">{{ $formatRupiah($surplusSisa) }}</td>
                <td class="text-center font-bold">-</td>
            </tr>

            <!-- ===================== 6. PEMBIAYAAN DAERAH ===================== -->
            @if(count($pembiayaan) > 0)
                <tr class="row-akun">
                    <td class="font-bold">6</td>
                    <td class="font-bold">PEMBIAYAAN DAERAH</td>
                    <td class="text-right font-bold">{{ $formatRupiah($totPembiayaanAnggaran) }}</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">{{ $formatRupiah($totPembiayaanAnggaran) }}</td>
                    <td class="text-center font-bold">-</td>
                </tr>

                @foreach($pembiayaan as $pm)
                    @php
                        $subPm = collect($pm['items'])->sum('anggaran');
                    @endphp
                    <tr class="row-kelompok">
                        <td class="font-bold" style="padding-left: 8px;">{{ $pm['kode'] }}</td>
                        <td class="font-bold" style="padding-left: 8px;">{{ $pm['nama'] }}</td>
                        <td class="text-right font-bold">{{ $formatRupiah($subPm) }}</td>
                        <td class="text-right font-bold">-</td>
                        <td class="text-right font-bold">-</td>
                        <td class="text-right font-bold">-</td>
                        <td class="text-right font-bold">{{ $formatRupiah($subPm) }}</td>
                        <td class="text-center font-bold">-</td>
                    </tr>

                    @foreach($pm['items'] as $i)
                        <tr>
                            <td style="padding-left: 16px;">{{ $i['kode'] }}</td>
                            <td style="padding-left: 16px;">{{ $i['nama'] }}</td>
                            <td class="text-right">{{ $formatRupiah($i['anggaran']) }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right">-</td>
                            <td class="text-right">-</td>
                            <td class="text-right">{{ $formatRupiah($i['anggaran']) }}</td>
                            <td class="text-center">-</td>
                        </tr>
                    @endforeach
                @endforeach

                <tr class="row-subtotal">
                    <td colspan="2" class="text-center font-bold uppercase">PEMBIAYAAN NETTO</td>
                    <td class="text-right font-bold">{{ $formatRupiah($totPembiayaanAnggaran) }}</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">-</td>
                    <td class="text-right font-bold">{{ $formatRupiah($totPembiayaanAnggaran) }}</td>
                    <td class="text-center font-bold">-</td>
                </tr>
            @endif

            <!-- ===================== SILPA TAHUN BERJALAN ===================== -->
            <tr class="row-silpa">
                <td colspan="2" class="text-center font-bold uppercase">SISA LEBIH PEMBIAYAAN ANGGARAN (SILPA) TAHUN BERJALAN</td>
                <td class="text-right font-bold">{{ $formatRupiah($silpaAnggaran) }}</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">-</td>
                <td class="text-right font-bold">{{ $formatRupiah($silpaSampai) }}</td>
                <td class="text-right font-bold">-</td>
                <td class="text-center font-bold">-</td>
            </tr>
        </tbody>
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
