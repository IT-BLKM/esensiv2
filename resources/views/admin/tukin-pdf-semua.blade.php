<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 7px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 52px; padding-right: 8px; }
        h1 { font-size: 13px; margin: 0 0 1px; }
        .kantor { font-size: 9px; font-weight: bold; margin: 0; }
        .alamat { font-size: 7.5px; margin: 0; font-style: italic; color: #374151; }
        .periode { margin: 7px 0 5px; font-size: 8px; font-weight: bold; }
        .ringkas { margin: 0 0 5px; font-size: 7.5px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #4b5563; padding: 2px 2px; text-align: center; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 6.5px; }
        table.data td { font-size: 6.8px; }
        table.data tr.kena td { background: #fef2f2; color: #991b1b; }
        .kiri { text-align: left; }
        .footer { margin-top: 10px; font-size: 7px; }
        table.ttd { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.ttd td { vertical-align: top; font-size: 7px; }
        .ruang { height: 34px; }
        .kanan { text-align: right; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="50" alt="Logo Kemenkes"></td>
            @endif
            <td>
                <h1>Rekap Potongan Tunjangan Kinerja</h1>
                <p class="kantor">{{ $namaKantor }}</p>
                <p class="alamat">{{ $alamatKantor }}</p>
            </td>
        </tr>
    </table>

    <div class="periode">Periode {{ $data['labelPeriode'] }}</div>
    <div class="ringkas">
        {{ $data['ringkasan']['pegawaiKenaPotongan'] }} dari {{ $data['ringkasan']['totalPegawai'] }} pegawai
        memiliki potongan. Total potongan: TL {{ number_format($data['ringkasan']['totalPersenTl'], 2, ',', '.') }}%,
        PSW {{ number_format($data['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%,
        keseluruhan {{ number_format($data['ringkasan']['totalPersen'], 2, ',', '.') }}%.
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>No.</th>
                <th class="kiri">NIP</th>
                <th class="kiri">Nama Pegawai</th>
                <th class="kiri">Departemen</th>
                <th>TL1</th>
                <th>TL2</th>
                <th>TL3</th>
                <th>PSW1</th>
                <th>PSW2</th>
                <th>PSW3</th>
                <th>PSW4</th>
                <th>Diganti</th>
                <th>% TL</th>
                <th>% PSW</th>
                <th>Total %</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['baris'] as $item)
                @php($r = $item['ringkasan'])
                <tr @if ($r['totalPersen'] > 0) class="kena" @endif>
                    <td>{{ $loop->iteration }}</td>
                    <td class="kiri">{{ $item['nip'] }}</td>
                    <td class="kiri">{{ $item['nama'] }}</td>
                    <td class="kiri">{{ $item['departemen'] ?? '-' }}</td>
                    <td>{{ $r['jumlahTl1'] }}</td>
                    <td>{{ $r['jumlahTl2'] }}</td>
                    <td>{{ $r['jumlahTl3'] }}</td>
                    <td>{{ $r['jumlahPsw1'] }}</td>
                    <td>{{ $r['jumlahPsw2'] }}</td>
                    <td>{{ $r['jumlahPsw3'] }}</td>
                    <td>{{ $r['jumlahPsw4'] }}</td>
                    <td>{{ $r['jumlahPenggantian'] }}</td>
                    <td>{{ number_format($r['totalPersenTl'], 2, ',', '.') }}%</td>
                    <td>{{ number_format($r['totalPersenPsw'], 2, ',', '.') }}%</td>
                    <td><strong>{{ number_format($r['totalPersen'], 2, ',', '.') }}%</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Persentase adalah potongan dari tunjangan kinerja, bukan nominal rupiah.
        TL/PSW yang sudah diganti tidak menambah potongan.
        Tanggal Cetak: {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}.
    </div>

    <table class="ttd">
        <tr>
            <td></td>
            <td class="kanan">
                Palembang, {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
                Ka. Subbag Administrasi Umum,
                <div class="ruang"></div>
                <strong>{{ auth()->user()?->nama ?? 'Administrator' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
