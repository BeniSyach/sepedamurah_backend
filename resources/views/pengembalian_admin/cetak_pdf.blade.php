<!DOCTYPE html>
<html>

<head>
    <title>Rekap Pengembalian</title>
    <style>
        body {
            font-family: "Calibri", sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <h2 style="text-align:center;">Laporan Rekap Pengembalian Dana</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Nama OPD</th>
                <th>Keterangan Tambahan</th>
                <th>Rekening Pengembalian</th>
                <th>Jumlah Pengembalian</th>
                <th>Tanggal Setor</th>
                <th>Jumlah Disetor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($data_pengembalian as $pengembalian)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $pengembalian->nik }}</td>
                    <td>{{ $pengembalian->nama }}</td>
                    <td>{{ $pengembalian->alamat }}</td>
                    <td>{{ $pengembalian->nm_opd }}</td>
                    <td>{{ $pengembalian->ket_tambahan }}</td>
                    <td>{{ $pengembalian->ket_pengembalian }}</td>
                    <td>{{ number_format($pengembalian->jml_pengembalian, 0, ',', '.') }}</td>
                    <td>{{ $pengembalian->tgl_setor }}</td>
                    <td>{{ number_format($pengembalian->jml_yg_disetor, 0, ',', '.') }}</td>
                    <td>{{ $pengembalian->keterangan }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
