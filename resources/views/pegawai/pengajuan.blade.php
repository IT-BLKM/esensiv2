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

                <div>
                    <label for="id_cuti" class="block text-sm font-medium text-gray-700">Jenis cuti</label>
                    <select
                        id="id_cuti"
                        name="id_cuti"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        @error('id_cuti') aria-invalid="true" @enderror
                    >
                        <option value="">-- Pilih jenis cuti --</option>
                        @foreach ($cuti as $item)
                            <option value="{{ $item->id }}" @selected((string) old('id_cuti') === (string) $item->id)>
                                {{ $item->kode }} — {{ $item->nama }}@if (($sisa[$item->id] ?? null) !== null) (sisa {{ $sisa[$item->id] }} hari)@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Wajib diisi bila jenis pengajuan Cuti. Kosongkan untuk Sakit dan Dinas Luar.</p>
                    @error('id_cuti')
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
                    <p class="mt-1 text-xs text-gray-500">Wajib untuk Sakit dan Cuti Alasan Penting. Format JPG/JPEG/PNG/PDF, maksimal 2 MB.</p>
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
</x-layouts.pegawai>
