<x-layouts.pegawai title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
            <p class="mt-1 text-sm text-gray-600">Ajukan cuti, sakit, atau dinas luar beserta lampirannya.</p>
        </div>

        @if (! $adaPegawai)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai. Hubungi administrator sebelum mengirim pengajuan.
            </x-alert>
        @endif

        @if ($cuti->isEmpty())
            <x-alert variant="warning" title="Jenis cuti belum tersedia">
                Hubungi administrator untuk menambah master jenis cuti sebelum mengajukan cuti.
            </x-alert>
        @endif

        <x-card title="Form pengajuan" subtitle="Pilih jenis, rentang tanggal, dan lampiran yang diminta.">
            <form method="POST" action="{{ route('pengajuan.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4" novalidate>
                @csrf

                <div>
                    <label for="jenis" class="block text-sm font-medium text-gray-700">Jenis pengajuan</label>
                    <select
                        id="jenis"
                        name="jenis"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        @error('jenis') aria-invalid="true" @enderror
                    >
                        <option value="cuti" @selected(old('jenis', 'cuti') === 'cuti')>Cuti</option>
                        <option value="sakit" @selected(old('jenis') === 'sakit')>Sakit</option>
                        <option value="dl" @selected(old('jenis') === 'dl')>Dinas Luar</option>
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
                            <option value="{{ $item->id }}" data-kode="{{ $item->kode }}" @selected((string) old('id_cuti') === (string) $item->id)>
                                {{ $item->kode }} — {{ $item->nama }}@if ($adaPegawai && $tersedia !== null) (tersedia {{ $tersedia }} hari)@elseif ($adaPegawai) (tanpa batas kuota)@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Wajib diisi bila jenis pengajuan Cuti. Kosongkan untuk Sakit dan Dinas Luar.</p>

                    @php
                        $cutiTahunan = $cuti->firstWhere('kode', 'C01');
                        $ringkasTahunan = $cutiTahunan === null ? null : ($sisa[$cutiTahunan->id] ?? null);
                    @endphp

                    @if ($adaPegawai && $ringkasTahunan !== null)
                        <p class="mt-1 text-xs text-gray-500">
                            Sisa kuota tahun {{ $tahunTampil }}: <strong class="text-gray-700">{{ $ringkasTahunan['sisaKuota'] }} hari</strong>;
                            maksimal terbawa ke {{ $tahunTampil + 1 }}: <strong class="text-gray-700">{{ $ringkasTahunan['maksBawaTahunDepan'] }} hari</strong>.
                            Total tersedia termasuk tabungan: {{ $ringkasTahunan['totalTersedia'] }} hari.
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
                        <option value="full" @selected(old('mode_dl') === 'full')>
                            Penuh — tidak perlu absen
                        </option>
                        <option value="setengah" @selected(old('mode_dl') === 'setengah')>
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

                @php
                    $idCutiLama = old('id_cuti');
                    $cutiTerpilih = $idCutiLama ? $cuti->firstWhere('id', (int) $idCutiLama) : null;
                    $tampilkanAlasanHaji = old('jenis', 'cuti') === 'cuti'
                        && $cutiTerpilih?->kode === 'C04';
                @endphp

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
                            value="{{ old('tanggal_mulai') }}"
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
                            value="{{ old('tanggal_berakhir') }}"
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
                    >{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="lampiran" class="block text-sm font-medium text-gray-700">Lampiran</label>
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

                <div class="flex items-center gap-3">
                    <x-button type="submit">Kirim pengajuan</x-button>
                    <a href="{{ route('pengajuan.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">Batal</a>
                </div>
            </form>
        </x-card>
    </div>

    @push('scripts')
        @vite('resources/js/pengajuan.js')
    @endpush
</x-layouts.pegawai>
