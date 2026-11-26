<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 8px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 56px; padding-right: 8px; }
        h1 { font-size: 14px; margin: 0 0 1px; }
        .kantor { font-size: 9px; font-weight: bold; margin: 0; }
        .alamat { font-size: 7.5px; margin: 0; font-style: italic; color: #374151; }
        h2 { font-size: 12px; margin: 10px 0 6px; }
        table.identitas { border-collapse: collapse; margin: 8px 0; font-size: 8.5px; }
        table.identitas td { padding: 1px 5px 1px 0; vertical-align: top; }
        table.identitas td.label { width: 72px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #374151; padding: 3px 4px; }
        table.data th { background: #e5e7eb; font-size: 7.5px; text-align: center; }
        table.data td { font-size: 8px; }
        .center { text-align: center; }
        .right { text-align: right; }
        table.total { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.total td { padding: 4px 6px; border: 1px solid #d1d5db; font-size: 8.5px; }
        table.total td.label { width: 65%; font-weight: bold; }
        .ttd { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .ttd td { width: 50%; text-align: center; font-size: 8px; vertical-align: bottom; }
        .ruang { height: 50px; }
        .muted { color: #6b7280; font-size: 7px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="54" alt="Logo Kemenkes"></td>
            @endif
            <td>
                <h1>Slip Potongan Tunjangan Kinerja</h1>
                <p class="kantor">{{ $namaKantor }}</p>
                <p class="alamat">{{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <h2>Periode {{ $labelPeriode }}</h2>

    <table class="identitas">
        <tr><td class="label">NIP</td><td>:</td><td>{{ $pegawai->nip }}</td></tr>
        <tr><td class="label">Nama</td><td>:</td><td>{{ $pegawai->nama }}</td></tr>
        <tr><td class="label">Jabatan</td><td>:</td><td>{{ $pegawai->jabatan }}</td></tr>
        <tr><td class="label">Unit Kerja</td><td>:</td><td>{{ $pegawai->departemen?->nama ?? '-' }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Komponen</th>
                <th class="center">Jumlah</th>
                <th class="right">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @php($r = $data['ringkasan'])
            <tr><td>Terlambat TL1</td><td class="center">{{ $r['jumlahTl1'] }}</td><td class="right">{{ number_format($r['jumlahTl1'] * $r['persenTingkat']['TL1'], 2, ',', '.') }}%</td></tr>
            <tr><td>Terlambat TL2</td><td class="center">{{ $r['jumlahTl2'] }}</td><td class="right">{{ number_format($r['jumlahTl2'] * $r['persenTingkat']['TL2'], 2, ',', '.') }}%</td></tr>
            <tr><td>Terlambat TL3</td><td class="center">{{ $r['jumlahTl3'] }}</td><td class="right">{{ number_format($r['jumlahTl3'] * $r['persenTingkat']['TL3'], 2, ',', '.') }}%</td></tr>
            <tr><td>PSW1</td><td class="center">{{ $r['jumlahPsw1'] }}</td><td class="right">{{ number_format($r['jumlahPsw1'] * $r['persenTingkat']['PSW1'], 2, ',', '.') }}%</td></tr>
            <tr><td>PSW2</td><td class="center">{{ $r['jumlahPsw2'] }}</td><td class="right">{{ number_format($r['jumlahPsw2'] * $r['persenTingkat']['PSW2'], 2, ',', '.') }}%</td></tr>
            <tr><td>PSW3</td><td class="center">{{ $r['jumlahPsw3'] }}</td><td class="right">{{ number_format($r['jumlahPsw3'] * $r['persenTingkat']['PSW3'], 2, ',', '.') }}%</td></tr>
            <tr><td>PSW4</td><td class="center">{{ $r['jumlahPsw4'] }}</td><td class="right">{{ number_format($r['jumlahPsw4'] * $r['persenTingkat']['PSW4'], 2, ',', '.') }}%</td></tr>
            @foreach ($data['koreksi'] ?? [] as $koreksi)
                <tr>
                    <td>Koreksi manual: {{ $koreksi['alasan'] }}</td>
                    <td class="center">—</td>
                    <td class="right">{{ number_format($koreksi['persen'], 2, ',', '.') }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total">
        <tr><td class="label">Total potongan sebelum koreksi</td><td class="right">{{ number_format($r['totalPersenAwal'], 2, ',', '.') }}%</td></tr>
        <tr><td class="label">Koreksi manual</td><td class="right">{{ number_format($r['koreksiPersen'], 2, ',', '.') }}%</td></tr>
        <tr><td class="label">Total potongan</td><td class="right"><strong>{{ number_format($r['totalPersenAkhir'], 2, ',', '.') }}%</strong></td></tr>
        <tr>
            <td class="label">Nominal dasar TUKIN</td>
            <td class="right">{{ $r['nominal'] === null ? 'Belum diatur' : 'Rp '.number_format($r['nominal'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Potongan (Rp)</td>
            <td class="right"><strong>{{ $r['potonganNominal'] === null ? '—' : 'Rp '.number_format($r['potonganNominal'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <p class="muted">
        Persentase mengikuti aturan TUKIN yang berlaku. TL/PSW yang sudah diganti tidak menambah potongan.
        Koreksi manual tercatat di sistem beserta pembuat dan alasannya.
    </p>

    <table class="ttd">
        <tr>
            <td>
                Pegawai yang bersangkutan
                <div class="ruang"></div>
                <strong>{{ $pegawai->nama }}</strong>
            </td>
            <td>
                Palembang, {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
                Pejabat berwenang
                <div class="ruang"></div>
                <strong>{{ auth()->user()?->nama ?? 'Administrator' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
