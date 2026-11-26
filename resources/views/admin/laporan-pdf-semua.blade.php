<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 4.4px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 38px; padding-right: 5px; }
        h1 { font-size: 10px; margin: 0 0 1px; }
        .kantor { font-size: 7px; font-weight: bold; margin: 0; }
        .alamat { font-size: 6px; margin: 0; font-style: italic; color: #374151; }
        .periode { margin: 4px 0 1px; text-align: right; font-size: 6px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td {
            border: 1px solid #4b5563;
            padding: 0 1px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.0;
            white-space: nowrap;
            overflow: hidden;
        }
        table.data th { background: #e5e7eb; font-size: 4.2px; }
        table.data td { font-size: 4.2px; }
        .nip { text-align: left; width: 8%; }
        .nama { text-align: left; width: 13%; white-space: normal; }
        .ringkas { width: 2.5%; font-weight: bold; }
        .libur { background: #fecaca; }
        .libur-nasional { background: #fde68a; }
        .kode-dl { background: #dbeafe; }
        .kode-c { background: #ede9fe; }
        .kode-s { background: #fef3c7; }
        .kode-tl { background: #fee2e2; font-weight: bold; color: #991b1b; }
        .kode-psw { background: #e0f2fe; font-weight: bold; color: #075985; }
        .kode-gabungan { background: #f3e8ff; font-weight: bold; color: #6b21a8; }
        table.footer { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.footer td { vertical-align: top; font-size: 5px; }
        .legenda { width: 65%; padding-right: 6px; line-height: 1.45; }
        .ttd { width: 35%; text-align: center; line-height: 1.55; }
        .ttd .ruang { height: 28px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="36" alt="Logo Kemenkes"></td>
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
                            $barisKode = $kode === null ? [] : explode("\n", (string) $kode);
                            $kelas = match (true) {
                                $kode === 'DL' => 'kode-dl',
                                $kode === 'C' => 'kode-c',
                                $kode === 'S' => 'kode-s',
                                is_string($kode) && str_contains($kode, "\n") => 'kode-gabungan',
                                is_string($kode) && str_starts_with($kode, 'PSW') => 'kode-psw',
                                is_string($kode) && (str_starts_with($kode, 'TL') || $kode === 'H!') => 'kode-tl',
                                $kode === null && in_array($hari, $data['hariLibur'] ?? [], true) => 'libur-nasional',
                                $kode === null && in_array($hari, $data['akhirPekan'] ?? [], true) => 'libur',
                                default => '',
                            };
                        @endphp
                        <td class="{{ $kelas }}">
                            {!! nl2br(e(implode("\n", $barisKode))) !!}
                        </td>
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
                Kode: H = hadir, H! = terlambat tetapi sudah diganti (tidak memotong tukin),
                TL1/TL2/TL3 = terlambat yang memotong tukin, PSW1–PSW4 = pulang sebelum waktu,
                DL = dinas luar, C = cuti, S = sakit, A = alpa. Sel kosong = hari tanpa shift atau hari libur.
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
