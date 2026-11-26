@php
    $pengajuan = $pengajuan ?? null;
    $metode = $metode ?? 'POST';
    $jenisTerpilih = old('jenis', $pengajuan?->jenis ?? 'cuti');
    $idCutiTerpilih = old('id_cuti', $pengajuan?->id_cuti);
    $modeDlTerpilih = old('mode_dl', $pengajuan?->mode_dl);
    $cutiTerpilih = $idCutiTerpilih === null
        ? null
        : $cuti->firstWhere('id', (int) $idCutiTerpilih);
    $tampilkanAlasanHaji = $jenisTerpilih === 'cuti' && $cutiTerpilih?->kode === 'C04';
    $cutiTahunan = $cuti->firstWhere('kode', 'C01');
    $ringkasTahunan = $cutiTahunan === null ? null : ($sisa[$cutiTahunan->id] ?? null);
    $bisaLangsungSetujui = $bisaLangsungSetujui ?? false;
@endphp

<form method="POST" action="{{ $aksi }}" enctype="multipart/form-data" class="flex flex-col gap-4" novalidate>
    @csrf
    @if ($metode !== 'POST')
        @method($metode)
    @endif

    @isset($daftarPegawai)
        <div>
            <label for="id_pegawai" class="block text-sm font-medium text-gray-700">Pegawai</label>
            <select
                id="id_pegawai"
                name="id_pegawai"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                @error('id_pegawai') aria-invalid="true" @enderror
            >
                <option value="">Pilih pegawai</option>
                @foreach ($daftarPegawai as $itemPegawai)
                    <option value="{{ $itemPegawai->id }}" @selected((string) old('id_pegawai', $pegawaiTerpilih ?? '') === (string) $itemPegawai->id)>
                        {{ $itemPegawai->nama }} ({{ $itemPegawai->nip }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                @if ($bisaLangsungSetujui)
                    Pengajuan dapat langsung disetujui dengan centang di bagian bawah form.
                @else
                    Pengajuan disimpan atas nama pegawai ini dan tetap berstatus menunggu.
                @endif
            </p>
            @error('id_pegawai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endisset

    <div>
        <label for="jenis" class="block text-sm font-medium text-gray-700">Jenis pengajuan</label>
        <select
            id="jenis"
            name="jenis"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
            @error('jenis') aria-invalid="true" @enderror
        >
            <option value="cuti" @selected($jenisTerpilih === 'cuti')>Cuti</option>
            <option value="sakit" @selected($jenisTerpilih === 'sakit')>Sakit</option>
            <option value="dl" @selected($jenisTerpilih === 'dl')>Dinas Luar</option>
        </select>
        @error('jenis')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div id="blok-jenis-cuti">
        <label for="id_cuti" class="block text-sm font-medium text-gray-700">Jenis cuti</label>
        <select
            id="id_cuti"
            name="id_cuti"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
            @error('id_cuti') aria-invalid="true" @enderror
        >
            <option value="">-- Pilih jenis cuti --</option>
            @foreach ($cuti as $item)
                @php
                    $ringkas = $sisa[$item->id] ?? null;
                    $tersedia = $ringkas['totalTersedia'] ?? null;
                @endphp
                <option value="{{ $item->id }}" data-kode="{{ $item->kode }}" @selected((string) $idCutiTerpilih === (string) $item->id)>
                    {{ $item->kode }} — {{ $item->nama }}@if ($adaPegawai && $tersedia !== null) (tersedia {{ $tersedia }} hari)@elseif ($adaPegawai) (tanpa batas kuota)@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Wajib diisi bila jenis pengajuan Cuti. Kosongkan untuk Sakit dan Dinas Luar.</p>

        @if ($adaPegawai && $ringkasTahunan !== null)
            <p class="mt-1 text-xs text-gray-500">
                Sisa kuota tahun {{ $tahunTampil }}: <strong class="text-gray-700">{{ $ringkasTahunan['sisaKuota'] }} hari</strong>;
                maksimal terbawa ke {{ $tahunTampil + 1 }}: <strong class="text-gray-700">{{ $ringkasTahunan['maksBawaTahunDepan'] }} hari</strong>.
                Total tersedia termasuk tabungan: <strong class="text-gray-700">{{ $ringkasTahunan['totalTersedia'] }} hari</strong>.
            </p>
        @endif

        @error('id_cuti')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div id="blok-mode-dl" class="hidden">
        <label for="mode_dl" class="block text-sm font-medium text-gray-700">Mode Dinas Luar</label>
        <select
            id="mode_dl"
            name="mode_dl"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
            @error('mode_dl') aria-invalid="true" @enderror
        >
            <option value="">-- Pilih mode dinas luar --</option>
            <option value="full" @selected($modeDlTerpilih === 'full')>
                Penuh — tidak perlu absen
            </option>
            <option value="setengah" @selected($modeDlTerpilih === 'setengah')>
                Setengah hari — absen sekali
            </option>
        </select>
        <p class="mt-1 text-xs text-gray-500">
            Penuh: hari kerja dianggap Dinas Luar dan tidak perlu presensi.
            Setengah: cukup absen sekali pada hari itu, tanpa absen pulang dan tidak dihitung terlambat.
        </p>
        @error('mode_dl')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div
        id="blok-alasan-haji"
        @class([
            'rounded-md border border-gray-200 bg-gray-50 px-3 py-2',
            'hidden' => ! $tampilkanAlasanHaji,
        ])
    >
        <label for="alasan_haji" class="flex items-start gap-2 text-sm text-gray-700">
            <input
                type="checkbox"
                id="alasan_haji"
                name="alasan_haji"
                value="1"
                @checked(old('alasan_haji'))
                @if (! $tampilkanAlasanHaji) disabled @endif
                class="mt-0.5 h-4 w-4 rounded border-gray-300 accent-brand-600"
            >
            <span>
                <span class="font-medium text-gray-900">Cuti Besar dengan alasan haji</span>
                <span class="block text-xs text-gray-600">
                    Centang hanya untuk Cuti Besar (C04). Tanpa centang, C04 mensyaratkan
                    5 tahun tidak pernah mengambil cuti besar.
                </span>
            </span>
        </label>
        @error('alasan_haji')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal mulai</label>
            <x-input
                type="date"
                name="tanggal_mulai"
                id="tanggal_mulai"
                value="{{ old('tanggal_mulai', $pengajuan?->tanggal_mulai) }}"
                :invalid="$errors->has('tanggal_mulai')"
            />
            @error('tanggal_mulai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tanggal_berakhir" class="block text-sm font-medium text-gray-700">Tanggal berakhir</label>
            <x-input
                type="date"
                name="tanggal_berakhir"
                id="tanggal_berakhir"
                value="{{ old('tanggal_berakhir', $pengajuan?->tanggal_berakhir) }}"
                :invalid="$errors->has('tanggal_berakhir')"
            />
            @error('tanggal_berakhir')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
        <textarea
            id="keterangan"
            name="keterangan"
            rows="3"
            maxlength="255"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
            @error('keterangan') aria-invalid="true" @enderror
        >{{ old('keterangan', $pengajuan?->keterangan) }}</textarea>
        @error('keterangan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="lampiran" class="block text-sm font-medium text-gray-700">Lampiran</label>

        @if ($pengajuan?->lampiran !== null)
            <p class="mt-1 text-xs text-gray-600">
                Lampiran saat ini:
                <a
                    href="{{ route('pengajuan.lampiran', $pengajuan) }}"
                    class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                >Unduh lampiran</a>.
                Kosongkan input di bawah bila tidak ingin menggantinya.
            </p>
        @endif

        <x-input
            type="file"
            name="lampiran"
            id="lampiran"
            accept=".jpg,.jpeg,.png,.pdf"
            :invalid="$errors->has('lampiran')"
        />
        <p class="mt-1 text-xs text-gray-500">Wajib untuk Sakit lebih dari 3 hari kerja dan Cuti Alasan Penting. Format JPG/JPEG/PNG/PDF, maksimal 2 MB.</p>
        @error('lampiran')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($bisaLangsungSetujui)
        <div class="rounded-md border border-brand-200 bg-brand-50 px-3 py-2">
            <label for="langsung_setujui" class="flex items-start gap-2 text-sm text-gray-700">
                <input
                    type="checkbox"
                    id="langsung_setujui"
                    name="langsung_setujui"
                    value="1"
                    @checked(old('langsung_setujui'))
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 accent-brand-600"
                >
                <span>
                    <span class="font-medium text-gray-900">Setujui langsung</span>
                    <span class="block text-xs text-gray-600">
                        Gunakan untuk input data historis. Pengajuan langsung disetujui dan baris presensi
                        hari kerja dibuat dalam satu transaksi.
                    </span>
                </span>
            </label>
            @error('langsung_setujui')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="flex items-center gap-3">
        <x-button type="submit">{{ $tombol ?? 'Kirim pengajuan' }}</x-button>
        <a href="{{ $batal ?? route('pengajuan.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">Batal</a>
    </div>
</form>
