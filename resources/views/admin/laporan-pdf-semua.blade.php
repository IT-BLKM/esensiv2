<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 6.5px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 42px; padding-right: 6px; }
        h1 { font-size: 11px; margin: 0 0 1px; }
        .kantor { font-size: 8px; font-weight: bold; margin: 0; }
        .alamat { font-size: 7px; margin: 0; font-style: italic; color: #374151; }
        .periode { margin: 5px 0 2px; text-align: right; font-size: 7px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #4b5563; padding: 1px 1px; text-align: center; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 6px; }
        table.data td { font-size: 6px; }
        table.data tr.terlambat td { color: #991b1b; background: #fef2f2; }
        .nip { text-align: left; width: 8%; }
        .nama { text-align: left; width: 16%; }
        .ringkas { width: 3%; font-weight: bold; }
        .libur { background: #fecaca; }
        .kode-dl { background: #dbeafe; }
        .kode-c { background: #ede9fe; }
        .kode-s { background: #fef3c7; }
        .kode-tl { background: #fee2e2; font-weight: bold; }
        table.footer { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.footer td { vertical-align: top; font-size: 6px; }
        .legenda { width: 65%; padding-right: 8px; line-height: 1.5; }
        .ttd { width: 35%; text-align: center; line-height: 1.6; }
        .ttd .ruang { height: 34px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="40" alt="Logo"></td>
            @endif
            <td>
                <h1>Laporan Presensi Pegawai Periode {{ $data['labelPeriode'] }}</h1>
                <p class="kantor">{{ $namaKantor }}</p>
                <p class="alamat">{{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <div class="periode">Bulan {{ $data['labelPeriode'] }}</div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2" class="nip">NIP</th>
                <th rowspan="2" class="nama">Nama Pegawai</th>
                <th colspan="{{ count($data['hari']) }}">Bulan {{ $data['labelPeriode'] }}</th>
                <th rowspan="2" class="ringkas">H</th>
                <th rowspan="2" class="ringkas">DL</th>
                <th rowspan="2" class="ringkas">C</th>
                <th rowspan="2" class="ringkas">S</th>
                <th rowspan="2" class="ringkas">A</th>
                <th rowspan="2" class="ringkas">TL</th>
                <th rowspan="2" class="ringkas">PSW</th>
            </tr>
            <tr>
                @foreach ($data['hari'] as $hari)
                    <th>{{ $hari }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['baris'] as $item)
                <tr @if ($item['tl'] > 0 || $item['psw'] > 0) class="terlambat" @endif>
                    <td class="nip">{{ $item['nip'] }}</td>
                    <td class="nama">{{ $item['nama'] }}</td>
                    @foreach ($data['hari'] as $hari)
                        @php
                            $kode = $item['sel'][$hari] ?? null;
                            $kelas = match ($kode) {
                                'DL' => 'kode-dl',
                                'C' => 'kode-c',
                                'S' => 'kode-s',
                                'H!' => 'kode-tl',
                                default => $kode === null && in_array($hari, $data['akhirPekan'] ?? [], true) ? 'libur' : '',
                            };
                        @endphp
                        <td class="{{ $kelas }}">{{ $kode }}</td>
                    @endforeach
                    <td class="ringkas">{{ $item['h'] }}</td>
                    <td class="ringkas">{{ $item['dl'] }}</td>
                    <td class="ringkas">{{ $item['c'] }}</td>
                    <td class="ringkas">{{ $item['s'] }}</td>
                    <td class="ringkas">{{ $item['a'] }}</td>
                    <td class="ringkas">{{ $item['tl'] }}</td>
                    <td class="ringkas">{{ $item['psw'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td class="legenda">
                Kode: H = hadir, H! = hadir terlambat, DL = dinas luar, C = cuti, S = sakit, A = alpa,
                TL = hari terlambat, PSW = pulang sebelum waktu. Sel kosong = hari tanpa shift.
                <br>
                Tanggal Cetak : {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}
            </td>
            <td class="ttd">
                Palembang, {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
                Ka. Subbag Administrasi Umum,
                <div class="ruang"></div>
                <strong>{{ auth()->user()?->nama ?? 'Administrator' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
