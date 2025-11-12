<!DOCTYPE html>
<html>

<head>
    <title>Data Pengembalian</title>
    <style>
        body {
            font-family: "Calibri", sans-serif;
            font-size: 14px;
            color: #000;
            margin-left: 40px;
            margin-left: 40px;
        }

        .header-logo {
            display: block;
            margin: 0 auto;
            width: 100px;
        }

        .header-title {
            text-align: center;
            margin-top: 10px;
        }

        .header-title h2 {
            margin: 0;
            font-size: 22px;
            text-decoration: underline;
            font-weight: bold;
        }

        .data-table td {
            padding: 6px 4px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .footer-logo {
            position: fixed;
            bottom: 0px;
            left: 0;
            width: 100%;
        }

        .footer-logo img {
            width: 100%;
        }

        hr {
            border: 0;
            border-top: 1px solid #000;
            margin: 5px 0 10px 0;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="center">
        <img src="{{ public_path('assets/img/logo_pemkab.png') }}" class="header-logo">
    </div>

    <div class="header-title">
        <h2>DATA PENGEMBALIAN</h2>
    </div>

    <br><br>

    {{-- DATA UTAMA --}}
    <table class="data-table">
        <tr>
            <td width="30%">Nama Rekening</td>
            <td width="3%">:</td>
            <td>{{ $rekening }}</td>
        </tr>
        <tr>
            <td>NIK</td>
            <td>:</td>
            <td>{{ $nik }}</td>
        </tr>
        <tr>
            <td>Atas Nama</td>
            <td>:</td>
            <td>{{ $nama }}</td>
        </tr>
        <tr>
            <td>Jumlah yang harus dikembalikan</td>
            <td>:</td>
            <td>{{ $jumlah }}</td>
        </tr>
        <tr>
            <td>Terbilang</td>
            <td>:</td>
            <td><i>{{ ucfirst($terbilang) }} rupiah</i></td>
        </tr>
        <tr>
            <td>Keterangan</td>
            <td>:</td>
            <td>{{ $keterangan }}</td>
        </tr>
    </table>

    <br><br>

    {{-- NO STS --}}
    <table class="data-table">
        <tr>
            <td width="30%">No STS</td>
            <td width="3%">:</td>
            <td>
                @php
                    $sts = $no_billing;
                    $part1 = substr($sts, 0, 4);
                    $part2 = substr($sts, 4, 7);
                    $part3 = substr($sts, -10);
                    $angka_format = $part1 . '.' . $part2 . '.' . $part3;
                @endphp
                <b>{{ $angka_format }}</b>
            </td>
        </tr>
    </table>

    <br><br><br>

    {{-- TANGGAL & TTD --}}
    <table class="data-table" style="width: 100%; margin-top: 50px;">
        <tr>
            <td class="right">
                Sei Rampah, {{ $tanggal }}<br><br><br>
                <b>(...................................)</b>
            </td>
        </tr>
    </table>

    <br><br>

    {{-- LOGO SEPEDA --}}
    <div class="center">
        <img src="{{ public_path('assets/img/logo_sepeda.png') }}" style="width: 100px; margin-top: 5px;">
    </div>

    {{-- FOOTER --}}
    <div class="footer-logo">
        <img src="{{ public_path('assets/img/footer-bg.png') }}">
    </div>

</body>

</html>
