<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 7px; }
        .kop { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kop td { vertical-align: middle; }
        .logo { width: 42px; }
        h1 { font-size: 12px; margin: 0 0 1px; }
        .subtitle { font-size: 8px; margin: 0; color: #374151; }
        h2 { font-size: 9px; margin: 6px 0 4px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #6b7280; padding: 1px 2px; text-align: center; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 6px; }
        table.data td { font-size: 6px; }
        table.data tr.terlambat td { color: #991b1b; background: #fef2f2; }
        .nama { text-align: left; width: 16%; }
        .nip { text-align: left; width: 8%; }
        .ringkas { width: 3%; font-weight: bold; }
        .footer { margin-top: 6px; text-align: right; font-size: 6px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="40" alt="Logo"></td>
            @endif
            <td>
                <h1>Rekap Presensi Semua Pegawai</h1>
                <p class="subtitle">{{ $namaKantor }} · {{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <h2>Periode {{ $data['labelPeriode'] }}</h2>

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
            </tr>
            <tr>
                @foreach ($data['hari'] as $hari)
                    <th>{{ $hari }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['baris'] as $item)
                <tr @if ($item['tl'] > 0) class="terlambat" @endif>
                    <td class="nip">{{ $item['nip'] }}</td>
                    <td class="nama">{{ $item['nama'] }}</td>
                    @foreach ($data['hari'] as $hari)
                        <td>{{ $item['sel'][$hari] ?? '' }}</td>
                    @endforeach
                    <td class="ringkas">{{ $item['h'] }}</td>
                    <td class="ringkas">{{ $item['dl'] }}</td>
                    <td class="ringkas">{{ $item['c'] }}</td>
                    <td class="ringkas">{{ $item['s'] }}</td>
                    <td class="ringkas">{{ $item['a'] }}</td>
                    <td class="ringkas">{{ $item['tl'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Kode: H = hadir, H! = hadir terlambat, DL = dinas luar, C = cuti, S = sakit, A = alpa, TL = hari terlambat. Sel kosong = hari tanpa shift.<br>
        Tanggal Cetak: {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}
        · Dicetak oleh: {{ auth()->user()?->nama ?? 'Administrator' }}
    </div>
</body>
</html>
