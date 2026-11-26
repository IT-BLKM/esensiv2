<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 9px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 58px; padding-right: 8px; }
        h1 { font-size: 14px; margin: 0 0 1px; }
        .kantor { font-size: 10px; font-weight: bold; margin: 0; }
        .alamat { font-size: 8.5px; margin: 0; font-style: italic; color: #374151; }
        table.identitas { border-collapse: collapse; margin: 9px 0 7px; font-size: 9px; }
        table.identitas td { padding: 0 4px 1px 0; vertical-align: top; }
        table.identitas td.label { width: 68px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #111827; padding: 2px 3px; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 8px; text-align: center; }
        table.data td { font-size: 8px; }
        table.data tr.terlambat td { background: #fef2f2; }
        .foto { width: 38px; height: 28px; }
        .center { text-align: center; }
        .footer { margin-top: 14px; text-align: right; font-size: 8px; line-height: 1.6; }
        .footer .pemeriksa { font-weight: bold; font-style: italic; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="54" alt="Logo"></td>
            @endif
            <td>
                <h1>Laporan Presensi Pegawai Periode {{ $data['labelPeriode'] }}</h1>
                <p class="kantor">{{ $namaKantor }}</p>
                <p class="alamat">{{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <table class="identitas">
        <tr><td class="label">NIP</td><td>:</td><td>{{ $pegawai->nip }}</td></tr>
        <tr><td class="label">Nama</td><td>:</td><td>{{ $pegawai->nama }}</td></tr>
        <tr><td class="label">Jabatan</td><td>:</td><td>{{ $pegawai->jabatan }}</td></tr>
        <tr><td class="label">Unit Kerja</td><td>:</td><td>{{ $pegawai->departemen?->nama ?? '-' }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Foto Masuk</th>
                <th>Jam Pulang</th>
                <th>Foto Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Jml Jam</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $item)
                <tr @if ($item['menitTerlambat'] > 0 || $item['menitPulangCepat'] > 0) class="terlambat" @endif>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse((string) $item['tanggal'])->format('d-m-Y') }}</td>
                    <td class="center">{{ $item['jamMasuk'] ?? '-' }}</td>
                    <td class="center">
                        @if ($item['fotoMasuk'])
                            <img src="{{ $item['fotoMasuk'] }}" class="foto" alt="Foto masuk">
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ $item['jamKeluar'] ?? '-' }}</td>
                    <td class="center">
                        @if ($item['fotoKeluar'])
                            <img src="{{ $item['fotoKeluar'] }}" class="foto" alt="Foto pulang">
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{!! nl2br(e($item['kodeHari'])) !!}</td>
                    <td>{{ $item['keterangan'] }}</td>
                    <td class="center">{{ $item['jmlJam'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Tanggal Cetak : {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
        <span class="pemeriksa">Checked by Administrator</span>
    </div>
</body>
</html>
