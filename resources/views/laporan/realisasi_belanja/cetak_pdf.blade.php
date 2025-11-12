<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Realisasi Belanja</title>
    <style>
        @page {
            size: landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header,
        .content {
            text-align: center;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 5px;
        }

        table,
        td,
        th {
            border: 1px solid black;
        }

        .content-wrapper {
            border: 2px solid #000;
            padding: 15px;
            margin: 5px;
        }

        .bold {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .uppercase {
            text-transform: uppercase;
        }

        h4,
        h5,
        h6 {
            margin: 2px 0;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content">
            <div class="header">
                <table width="100%">
                    <tr align="center">
                        <td width="10%">
                            <img src="{{ public_path('assets/img/logo_pemkab.png') }}" width="60" height="70">
                        </td>
                        <td>
                            <h4><b>PEMERINTAH KABUPATEN SERDANG BEDAGAI</b></h4>
                            <h4><b>LAPORAN REALISASI BELANJA</b></h4>

                            @php
                                $months = [
                                    '01' => 'Januari',
                                    '02' => 'Februari',
                                    '03' => 'Maret',
                                    '04' => 'April',
                                    '05' => 'Mei',
                                    '06' => 'Juni',
                                    '07' => 'Juli',
                                    '08' => 'Agustus',
                                    '09' => 'September',
                                    '10' => 'Oktober',
                                    '11' => 'November',
                                    '12' => 'Desember',
                                ];
                                $periodeAwal = "01 Januari $tahun";
                                $bulanNama = $months[str_pad($bulan, 2, '0', STR_PAD_LEFT)];
                                $periodeAkhir = "31 $bulanNama $tahun";
                            @endphp

                            <h6>Periode {{ $periodeAwal }} s.d. {{ $periodeAkhir }}</h6>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="content">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2">Kode Rekening</th>
                            <th rowspan="2">Jenis Belanja</th>
                            <th colspan="3">Realisasi</th>
                        </tr>
                        <tr>
                            <th>s.d Bulan Lalu</th>
                            <th>Bulan Ini</th>
                            <th>s.d Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 1;
                            $total_sd_lalu = 0;
                            $total_bulan_ini = 0;
                            $total_sd_ini = 0;
                        @endphp

                        @php
                            $no = 1;
                            $total_sd_lalu = 0;
                            $total_bulan_ini = 0;
                            $total_sd_ini = 0;
                        @endphp

                        @foreach ($result as $rb)
                            @php
                                $belanja_sd_lalu = 0;
                                for ($i = 1; $i < $bulan; $i++) {
                                    $key = 'belanja_' . strtoupper(date('M', mktime(0, 0, 0, $i, 10)));
                                    $belanja_sd_lalu += $rb->$key ?? 0;
                                }

                                $key_bulan_ini = 'belanja_' . strtoupper(date('M', mktime(0, 0, 0, $bulan, 10)));
                                $belanja_bulan_ini = $rb->$key_bulan_ini ?? 0;

                                $belanja_sd_ini = $belanja_sd_lalu + $belanja_bulan_ini;

                                $total_sd_lalu += $belanja_sd_lalu;
                                $total_bulan_ini += $belanja_bulan_ini;
                                $total_sd_ini += $belanja_sd_ini;
                            @endphp

                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ implode('.', array_filter([$rb->kd_ref1, $rb->kd_ref2, $rb->kd_ref3])) }}</td>
                                <td>{{ $rb->jenis_belanja }}</td>
                                <td>Rp {{ number_format($belanja_sd_lalu, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($belanja_bulan_ini, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($belanja_sd_ini, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach

                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-center"><strong>Total</strong></td>
                            <td>Rp {{ number_format($total_sd_lalu, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_bulan_ini, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_sd_ini, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" align="center"><b>Total</b></td>
                            <td>Rp {{ number_format($total_sd_lalu, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_bulan_ini, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_sd_ini, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
