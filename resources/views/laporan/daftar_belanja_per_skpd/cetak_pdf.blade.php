<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Daftar Belanja Per SKPD</title>

    <style>
        @page {
            size: landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 20px;
        }

        .header,
        .content {
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            padding: 5px;
        }

        .content-wrapper {
            border: 2px solid #000;
            padding: 20px;
            margin: 5px;
        }

        .new-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 2px solid #000;
        }

        .new-table td {
            padding: 10px;
            border: 1px solid #000;
        }

        .bold {
            background-color: #f0f0f0;
        }

        .uppercase {
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">

        <!-- HEADER -->
        <section class="content">
            <div class="header">
                <table width="100%">
                    <tr align="center">
                        <td>
                            <img src="{{ public_path('assets/img/logo_pemkab.png') }}" width="60" height="70">
                        </td>
                        <td>
                            <h4><b>PEMERINTAHAN KABUPATEN SERDANG BEDAGAI</b></h4>
                            <h4><b>LAPORAN DAFTAR BELANJA PER SKPD</b></h4>
                            <h6>
                                Periode 01 Januari {{ $tahun }} s.d. 31 Desember {{ $tahun }}
                            </h6>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- SKPD -->
            <table style="margin-top: 10px;">
                <tr>
                    <td>
                        <h6>SKPD</h6>
                    </td>
                    <td>
                        <h6>:</h6>
                    </td>
                    <td>
                        <h6>
                            [
                            {{ $skpd->kd_opd1 }}.{{ $skpd->kd_opd2 }}.{{ $skpd->kd_opd3 }}.
                            {{ $skpd->kd_opd4 }}.{{ $skpd->kd_opd5 }}
                            {{ $skpd->nm_opd }}
                            ]
                        </h6>
                    </td>
                </tr>
            </table>

            <!-- TABEL DATA -->
            <table class="new-table">
                <tr>
                    <td class="bold uppercase">
                        <h6>No</h6>
                    </td>
                    <td class="bold uppercase">
                        <h6>Tanggal</h6>
                    </td>
                    <td class="bold uppercase">
                        <h6>Sumber Dana</h6>
                    </td>
                    <td class="bold uppercase">
                        <h6>Jenis Belanja</h6>
                    </td>
                    <td class="bold uppercase">
                        <h6>Jumlah</h6>
                    </td>
                </tr>

                @php
                    $no = 1;
                    $total = 0;
                @endphp

                @foreach ($result as $row)
                    @php
                        $total += $row->jumlah;
                    @endphp

                    <tr>
                        <td>
                            <h6>{{ $no++ }}</h6>
                        </td>
                        <td>
                            <h6>{{ $row->tanggal }}</h6>
                        </td>
                        <td>
                            <h6>{{ $row->sumber_dana }}</h6>
                        </td>
                        <td>
                            <h6>{{ $row->jenis_belanja }}</h6>
                        </td>
                        <td>
                            <h6>Rp {{ number_format($row->jumlah, 2, ',', '.') }}</h6>
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="4" class="text-right bold uppercase">
                        <h6>Total</h6>
                    </td>
                    <td>
                        <h6>Rp {{ number_format($total, 2, ',', '.') }}</h6>
                    </td>
                </tr>
            </table>

        </section>
    </div>
</body>

</html>
