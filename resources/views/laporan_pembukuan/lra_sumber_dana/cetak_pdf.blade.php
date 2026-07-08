<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: DejaVu Sans;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .judul {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .subjudul {
            text-align: center;
            font-size: 11px;
        }

        .garis {
            border-top: 2px solid #000;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        th {
            border: 1px solid #000;
            background: #E6E6E6;
            text-align: center;
            padding: 5px;
        }

        td {
            border: 1px solid #000;
            padding: 4px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .sumber {
            background: #D9D9D9;
            font-weight: bold;
        }

        .subtotal {
            font-weight: bold;
            background: #F4F4F4;
        }

        .grandtotal {
            font-weight: bold;
            background: #CFCFCF;
        }
    </style>

</head>

<body>

    <div class="judul">
        LAPORAN REALISASI ANGGARAN
    </div>

    <div class="subjudul">
        PER SUMBER DANA
    </div>

    <div class="subjudul">
        Periode
        {{ \Carbon\Carbon::parse($from)->format('d-m-Y') }}
        s/d
        {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}
    </div>

    <div class="garis"></div>

    <table>

        <thead>

            <tr>

                <th rowspan="2" width="13%">
                    KODE
                </th>

                <th rowspan="2">
                    URAIAN
                </th>

                <th rowspan="2" width="15%">
                    ANGGARAN
                </th>

                <th colspan="3">
                    REALISASI
                </th>

                <th rowspan="2" width="15%">
                    LEBIH / KURANG
                </th>

            </tr>

            <tr>

                <th width="12%">
                    s/d Lalu
                </th>

                <th width="12%">
                    Periode Ini
                </th>

                <th width="12%">
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            @php

                $grandTotal = 0;

            @endphp

            @foreach ($data as $sd)
                <tr class="sumber">

                    <td colspan="7">

                        {{ $sd['kode'] }}
                        -
                        {{ $sd['nama'] }}

                    </td>

                </tr>

                @foreach ($sd['belanja'] as $item)
                    <tr>

                        <td>

                            {{ $item['kode'] }}

                        </td>

                        <td style="padding-left:15px;">

                            {{ $item['nama'] }}

                        </td>

                        <td class="right">

                            -

                        </td>

                        <td class="right">

                            -

                        </td>

                        <td class="right">

                            {{ number_format($item['total'], 2, ',', '.') }}

                        </td>

                        <td class="right">

                            {{ number_format($item['total'], 2, ',', '.') }}

                        </td>

                        <td class="right">

                            -

                        </td>

                    </tr>
                @endforeach

                <tr class="subtotal">

                    <td colspan="5">

                        TOTAL {{ $sd['nama'] }}

                    </td>

                    <td class="right">

                        {{ number_format($sd['total'], 2, ',', '.') }}

                    </td>

                    <td></td>

                </tr>

                @php

                    $grandTotal += $sd['total'];

                @endphp
            @endforeach

            <tr class="grandtotal">

                <td colspan="5">

                    GRAND TOTAL

                </td>

                <td class="right">

                    {{ number_format($grandTotal, 2, ',', '.') }}

                </td>

                <td></td>

            </tr>

        </tbody>

    </table>

</body>

</html>
