<table>
    <tr>
        <td colspan="5" style="font-size:16px; font-weight:bold; text-align:center;">
            PEMERINTAHAN KABUPATEN SERDANG BEDAGAI
        </td>
    </tr>
    <tr>
        <td colspan="5" style="font-size:16px; font-weight:bold; text-align:center;">
            LAPORAN DAFTAR BELANJA PER SKPD
        </td>
    </tr>
    <tr>
        <td colspan="5" style="text-align:center;">
            Periode 01 Januari {{ $tahun }} s.d. 31 Desember {{ $tahun }}
        </td>
    </tr>

    <tr>
        <td colspan="5"></td>
    </tr>

    <tr>
        <td>SKPD</td>
        <td colspan="4">
            [{{ $skpd['KD_OPD1'] }}.{{ $skpd['KD_OPD2'] }}.{{ $skpd['KD_OPD3'] }}.{{ $skpd['KD_OPD4'] }}.{{ $skpd['KD_OPD5'] }}
            {{ $skpd['NM_OPD'] }}]
        </td>
    </tr>

    <tr>
        <td colspan="5"></td>
    </tr>

    <tr style="font-weight:bold; background:#f0f0f0;">
        <td>No</td>
        <td>Tanggal</td>
        <td>Sumber Dana</td>
        <td>Jenis Belanja</td>
        <td>Jumlah</td>
    </tr>

    @php
        $no = 1;
        $total = 0;
    @endphp

    @foreach ($data as $row)
        @php $total += $row->jumlah; @endphp
        <tr>
            <td>{{ $no++ }}</td>
            <td>{{ $row->tanggal }}</td>
            <td>{{ $row->sumber_dana }}</td>
            <td>{{ $row->jenis_belanja }}</td>
            <td>{{ $row->jumlah }}</td>
        </tr>
    @endforeach

    <tr style="font-weight:bold; background:#e0e0e0;">
        <td colspan="4">TOTAL</td>
        <td>{{ $total }}</td>
    </tr>
</table>
