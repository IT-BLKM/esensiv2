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
        .ringkasan { margin: 0 0 5px; font-size: 7.5px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #4b5563; padding: 2px 2px; text-align: center; vertical-align: middle; }
        table.data th { background: #e5e7eb; font-size: 6.2px; }
        table.data td { font-size: 6.6px; }
        table.data tr.kena td { background: #fef2f2; color: #991b1b; }
        .no { width: 4%; }
        .nip { width: 12%; white-space: nowrap; text-align: left; }
        .nama { width: 18%; text-align: left; }
        .ringkas { width: 4%; }
        .diganti { width: 6%; }
        .persen { width: 7%; }
        .total { width: 8%; }
        .nominal { width: 10%; }
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
    <div class="ringkasan">
        {{ $data['ringkasan']['pegawaiKenaPotongan'] }} dari {{ $data['ringkasan']['totalPegawai'] }} pegawai
        memiliki potongan. Total potongan: TL {{ number_format($data['ringkasan']['totalPersenTl'], 2, ',', '.') }}%,
        PSW {{ number_format($data['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%,
        koreksi {{ number_format($data['ringkasan']['totalKoreksi'], 2, ',', '.') }}%,
        setelah koreksi {{ number_format($data['ringkasan']['totalPersenAkhir'], 2, ',', '.') }}%.
        @if ($data['ringkasan']['totalNominalPotongan'] !== null)
            Total potongan rupiah: Rp {{ number_format($data['ringkasan']['totalNominalPotongan'], 0, ',', '.') }}.
        @endif
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="no">No.</th>
                <th class="nip">NIP</th>
                <th class="nama">Nama Pegawai</th>
                <th class="ringkas">TL1</th>
                <th class="ringkas">TL2</th>
                <th class="ringkas">TL3</th>
                <th class="ringkas">PSW1</th>
                <th class="ringkas">PSW2</th>
                <th class="ringkas">PSW3</th>
                <th class="ringkas">PSW4</th>
                <th class="diganti">Diganti</th>
                <th class="persen">% TL</th>
                <th class="persen">% PSW</th>
                <th class="total">Total akhir</th>
                <th class="nominal">Potongan Rp</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['baris'] as $item)
                @php($r = $item['ringkasan'])
                <tr @if ($r['totalPersenAkhir'] > 0) class="kena" @endif>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td class="nip">{{ $item['nip'] }}</td>
                    <td class="nama">{{ $item['nama'] }}</td>
                    <td class="ringkas">{{ $r['jumlahTl1'] }}</td>
                    <td class="ringkas">{{ $r['jumlahTl2'] }}</td>
                    <td class="ringkas">{{ $r['jumlahTl3'] }}</td>
                    <td class="ringkas">{{ $r['jumlahPsw1'] }}</td>
                    <td class="ringkas">{{ $r['jumlahPsw2'] }}</td>
                    <td class="ringkas">{{ $r['jumlahPsw3'] }}</td>
                    <td class="ringkas">{{ $r['jumlahPsw4'] }}</td>
                    <td class="diganti">{{ $r['jumlahPenggantian'] }}</td>
                    <td class="persen">{{ number_format($r['totalPersenTl'], 2, ',', '.') }}%</td>
                    <td class="persen">{{ number_format($r['totalPersenPsw'], 2, ',', '.') }}%</td>
                    <td class="total"><strong>{{ number_format($r['totalPersenAkhir'], 2, ',', '.') }}%</strong></td>
                    <td class="nominal">{{ $r['potonganNominal'] === null ? '—' : 'Rp '.number_format($r['potonganNominal'], 0, ',', '.') }}</td>
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
