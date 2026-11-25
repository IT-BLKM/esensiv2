<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 9px; }
        .kop { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .kop td { vertical-align: middle; }
        .logo { width: 60px; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .subtitle { font-size: 10px; margin: 0; color: #374151; }
        h2 { font-size: 12px; margin: 10px 0 6px; }
        table.identitas { border-collapse: collapse; margin-bottom: 8px; }
        table.identitas td { padding: 1px 4px 1px 0; vertical-align: top; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #374151; padding: 3px; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 8px; }
        table.data td { font-size: 8px; }
        .foto { width: 42px; height: 30px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .footer { margin-top: 14px; text-align: right; font-size: 8px; }
        table.rekap { margin-top: 10px; border-collapse: collapse; }
        table.rekap td { padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="56" alt="Logo"></td>
            @endif
            <td>
                <h1>Laporan Presensi Pegawai</h1>
                <p class="subtitle">{{ $namaKantor }}</p>
                <p class="subtitle">{{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <h2>Periode {{ $data['labelPeriode'] }}</h2>

    <table class="identitas">
        <tr><td>NIP</td><td>:</td><td>{{ $pegawai->nip }}</td></tr>
        <tr><td>Nama</td><td>:</td><td>{{ $pegawai->nama }}</td></tr>
        <tr><td>Jabatan</td><td>:</td><td>{{ $pegawai->jabatan }}</td></tr>
        <tr><td>Unit Kerja</td><td>:</td><td>{{ $pegawai->departemen?->nama ?? '-' }}</td></tr>
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
                <th>Keterlambatan</th>
                <th>Jml Jam</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $item)
                <tr>
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
                    <td class="center">{{ $item['statusLabel'] }}</td>
                    <td>{{ $item['keterangan'] }}</td>
                    <td class="center">{{ $item['jmlJam'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="rekap">
        <tr>
            <td><strong>Hadir (tepat waktu):</strong> {{ $data['ringkasan']['tepatWaktu'] }}</td>
            <td><strong>Terlambat:</strong> {{ $data['ringkasan']['terlambat'] }}</td>
            <td><strong>Cuti:</strong> {{ $data['ringkasan']['cuti'] }}</td>
            <td><strong>Sakit:</strong> {{ $data['ringkasan']['sakit'] }}</td>
            <td><strong>DL:</strong> {{ $data['ringkasan']['dl'] }}</td>
            <td><strong>Total terlambat:</strong> {{ $data['ringkasan']['totalMenitTerlambatTeks'] }}</td>
        </tr>
    </table>

    <div class="footer">
        Tanggal Cetak: {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
        Dicetak oleh: {{ auth()->user()?->nama ?? 'Administrator' }}
    </div>
</body>
</html>
