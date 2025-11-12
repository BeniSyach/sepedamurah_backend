<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th rowspan="2">No</th>
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

        @foreach ($result as $rb)
            @php
                $belanja_sd_lalu = 0;
                for ($i = 1; $i < $bulan; $i++) {
                    $key = 'belanja_' . strtolower(date('M', mktime(0, 0, 0, $i, 10)));
                    $belanja_sd_lalu += $rb->$key ?? 0;
                }
                $key_bulan_ini = 'belanja_' . strtolower(date('M', mktime(0, 0, 0, $bulan, 10)));
                $belanja_bulan_ini = $rb->$key_bulan_ini ?? 0;
                $belanja_sd_ini = $belanja_sd_lalu + $belanja_bulan_ini;

                $total_sd_lalu += $belanja_sd_lalu;
                $total_bulan_ini += $belanja_bulan_ini;
                $total_sd_ini += $belanja_sd_ini;
            @endphp
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ implode('.', array_filter([$rb['KD_REF1'], $rb['kd_ref2'], $rb['kd_ref3']])) }}</td>
                <td>{{ $rb->jenis_belanja }}</td>
                <td>{{ number_format($belanja_sd_lalu, 2, ',', '.') }}</td>
                <td>{{ number_format($belanja_bulan_ini, 2, ',', '.') }}</td>
                <td>{{ number_format($belanja_sd_ini, 2, ',', '.') }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="3"><b>Total</b></td>
            <td><b>{{ number_format($total_sd_lalu, 2, ',', '.') }}</b></td>
            <td><b>{{ number_format($total_bulan_ini, 2, ',', '.') }}</b></td>
            <td><b>{{ number_format($total_sd_ini, 2, ',', '.') }}</b></td>
        </tr>
    </tbody>
</table>
