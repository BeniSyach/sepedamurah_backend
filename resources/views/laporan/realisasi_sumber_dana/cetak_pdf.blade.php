<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laporan Realisasi Sumber Dana</title>
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
        }

        .uppercase {
            text-transform: uppercase;
        }

        .bold {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        h4,
        h5,
        h6 {
            margin: 2px 0;
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
                            <h4><b>PEMERINTAHAN KABUPATEN SERDANG BEDAGAI</b></h4>
                            <h4><b>LAPORAN REALISASI SUMBER DANA</b></h4>
                            <h6>BERDASARKAN KLASIFIKASI SUMBER DANA</h6>
                            <h6>
                                Periode 01 Januari {{ $tahun }} s.d. 31 Desember {{ $tahun }}
                            </h6>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="content">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Rekening</th>
                            <th>Uraian</th>
                            <th>Pagu Anggaran</th>
                            <th>Saldo Awal Tahun Lalu</th>
                            <th>Dana Masuk</th>
                            <th>Realisasi Belanja SKPD</th>
                            <th>Sisa Sumber Dana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 1;
                            $total_pagu = 0;
                            $total_saldo = 0;
                            $total_dana_masuk = 0;
                            $total_belanja = 0;
                            $total_sisa = 0;
                        @endphp

                        @foreach ($result as $sd)
                            @php
                                $total_pagu += $sd->pagu;
                                $total_saldo += $sd->jumlah_silpa;
                                $total_dana_masuk += $sd->sumber_dana;
                                $total_belanja += $sd->belanja;
                                $total_sisa += $sd->sisa;
                            @endphp
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>
                                    {{ implode('.', array_filter([$sd->kd_ref1, $sd->kd_ref2, $sd->kd_ref3, $sd->kd_ref4, $sd->kd_ref5, $sd->kd_ref6])) }}
                                </td>
                                <td>{{ $sd->nm_sumber }}</td>
                                <td>Rp {{ number_format($sd->pagu, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($sd->jumlah_silpa, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($sd->sumber_dana, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($sd->belanja, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($sd->sisa, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3" align="center"><b>Total</b></td>
                            <td>Rp {{ number_format($total_pagu, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_saldo, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_dana_masuk, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_belanja, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($total_sisa, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
