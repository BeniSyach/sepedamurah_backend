<table border="1">
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
                <td>{{ implode('.', array_filter([$sd->kd_ref1, $sd->kd_ref2, $sd->kd_ref3, $sd->kd_ref4, $sd->kd_ref5, $sd->kd_ref6])) }}
                </td>
                <td>{{ $sd->nm_sumber }}</td>
                <td>{{ $sd->pagu }}</td>
                <td>{{ $sd->jumlah_silpa }}</td>
                <td>{{ $sd->sumber_dana }}</td>
                <td>{{ $sd->belanja }}</td>
                <td>{{ $sd->sisa }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" align="center"><b>Total</b></td>
            <td>{{ $total_pagu }}</td>
            <td>{{ $total_saldo }}</td>
            <td>{{ $total_dana_masuk }}</td>
            <td>{{ $total_belanja }}</td>
            <td>{{ $total_sisa }}</td>
        </tr>
    </tfoot>
</table>
