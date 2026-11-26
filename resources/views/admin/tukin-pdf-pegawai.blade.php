<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 7px; }
        table.kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.kop td { vertical-align: middle; }
        .logo { width: 56px; padding-right: 8px; }
        h1 { font-size: 13px; margin: 0 0 1px; }
        .kantor { font-size: 9px; font-weight: bold; margin: 0; }
        .alamat { font-size: 7.5px; margin: 0; font-style: italic; color: #374151; }
        table.identitas { border-collapse: collapse; margin: 8px 0 7px; font-size: 8px; }
        table.identitas td { padding: 0 4px 1px 0; vertical-align: top; }
        table.identitas td.label { width: 66px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #374151; padding: 2px 3px; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 6.5px; text-align: center; }
        table.data td { font-size: 6.8px; }
        table.data tr.kena td { background: #fef2f2; color: #991b1b; }
        .center { text-align: center; }
        .right { text-align: right; }
        .footer { margin-top: 12px; text-align: right; font-size: 7px; line-height: 1.5; }
        table.rekap { margin-top: 8px; border-collapse: collapse; font-size: 7.5px; }
        table.rekap td { padding: 2px 10px 2px 0; }
        .ruang { height: 30px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" width="54" alt="Logo Kemenkes"></td>
            @endif
            <td>
                <h1>Laporan Potongan Tunjangan Kinerja</h1>
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
                <th>No.</th>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>TL</th>
                <th>Menit TL</th>
                <th>% TL</th>
                <th>PSW</th>
                <th>Menit PSW</th>
                <th>% PSW</th>
                <th>Total %</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['baris'] as $item)
                <tr @if ($item['persenTotal'] > 0) class="kena" @endif>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse((string) $item['tanggal'])->format('d-m-Y') }}</td>
                    <td class="center">{{ $item['hari'] }}</td>
                    <td>{{ $item['shift'] }}</td>
                    <td class="center">{{ $item['jamMasuk'] ?? '-' }}</td>
                    <td class="center">{{ $item['jamKeluar'] ?? '-' }}</td>
                    <td class="center">{{ $item['levelTl'] ?? '-' }}</td>
                    <td class="center">{{ $item['menitTerlambat'] ?? '-' }}</td>
                    <td class="center">{{ number_format($item['persenTl'], 2, ',', '.') }}%</td>
                    <td class="center">{{ $item['levelPsw'] ?? '-' }}</td>
                    <td class="center">{{ $item['menitPulangCepat'] ?? '-' }}</td>
                    <td class="center">{{ number_format($item['persenPsw'], 2, ',', '.') }}%</td>
                    <td class="center"><strong>{{ number_format($item['persenTotal'], 2, ',', '.') }}%</strong></td>
                    <td>{{ $item['catatanPenggantian'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="rekap">
        <tr>
            <td><strong>TL1:</strong> {{ $data['ringkasan']['jumlahTl1'] }}</td>
            <td><strong>TL2:</strong> {{ $data['ringkasan']['jumlahTl2'] }}</td>
            <td><strong>TL3:</strong> {{ $data['ringkasan']['jumlahTl3'] }}</td>
            <td><strong>PSW1:</strong> {{ $data['ringkasan']['jumlahPsw1'] }}</td>
            <td><strong>PSW2:</strong> {{ $data['ringkasan']['jumlahPsw2'] }}</td>
            <td><strong>PSW3:</strong> {{ $data['ringkasan']['jumlahPsw3'] }}</td>
            <td><strong>PSW4:</strong> {{ $data['ringkasan']['jumlahPsw4'] }}</td>
        </tr>
        <tr>
            <td><strong>Hari diganti:</strong> {{ $data['ringkasan']['jumlahPenggantian'] }}</td>
            <td><strong>Total % TL:</strong> {{ number_format($data['ringkasan']['totalPersenTl'], 2, ',', '.') }}%</td>
            <td><strong>Total % PSW:</strong> {{ number_format($data['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%</td>
            <td><strong>Koreksi:</strong> {{ number_format($data['ringkasan']['koreksiPersen'], 2, ',', '.') }}%</td>
        </tr>
        <tr>
            <td><strong>Total sebelum koreksi:</strong> {{ number_format($data['ringkasan']['totalPersenAwal'], 2, ',', '.') }}%</td>
            <td><strong>Total akhir:</strong> {{ number_format($data['ringkasan']['totalPersenAkhir'], 2, ',', '.') }}%</td>
            <td><strong>Nominal:</strong> {{ $data['ringkasan']['nominal'] === null ? '—' : 'Rp '.number_format($data['ringkasan']['nominal'], 0, ',', '.') }}</td>
            <td colspan="3"><strong>Potongan:</strong> {{ $data['ringkasan']['potonganNominal'] === null ? '—' : 'Rp '.number_format($data['ringkasan']['potonganNominal'], 0, ',', '.') }}</td>
        </tr>
        @if (! empty($data['koreksi']))
            <tr>
                <td colspan="7">
                    <strong>Koreksi manual:</strong>
                    @foreach ($data['koreksi'] as $koreksi)
                        {{ number_format($koreksi['persen'], 2, ',', '.') }}% ({{ $koreksi['alasan'] }})@if (! $loop->last), @endif
                    @endforeach
                </td>
            </tr>
        @endif
    </table>

    <div class="footer">
        Tanggal Cetak : {{ \Carbon\CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->format('d-m-Y') }}<br>
        <strong><em>Checked by Administrator</em></strong>
        <div class="ruang"></div>
        {{ auth()->user()?->nama ?? 'Administrator' }}
    </div>
</body>
</html>
