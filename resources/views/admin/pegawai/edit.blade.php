<x-layouts.admin title="Ubah Pegawai">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ubah pegawai</h1>
            <p class="mt-1 text-sm text-gray-600">Perbarui data {{ $pegawai->nama }} (NIP {{ $pegawai->nip }}). Password tidak berubah saat data disimpan.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.pegawai.update', $pegawai) }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nip" class="block text-sm font-medium text-gray-700">NIP</label>
                        <x-input
                            id="nip"
                            name="nip"
                            :value="old('nip', $pegawai->nip)"
                            :invalid="$errors->has('nip')"
                            maxlength="18"
                            inputmode="numeric"
                            class="mt-1"
                        />
                        @error('nip')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <x-input
                            id="email"
                            name="email"
                            type="email"
                            :value="old('email', $pegawai->user?->email)"
                            :invalid="$errors->has('email')"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <x-input
                            id="nama"
                            name="nama"
                            :value="old('nama', $pegawai->nama)"
                            :invalid="$errors->has('nama')"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('nama')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="no_hp" class="block text-sm font-medium text-gray-700">No. HP</label>
                        <x-input
                            id="no_hp"
                            name="no_hp"
                            :value="old('no_hp', $pegawai->no_hp)"
                            :invalid="$errors->has('no_hp')"
                            maxlength="15"
                            inputmode="numeric"
                            class="mt-1"
                        />
                        @error('no_hp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                        <x-input
                            id="jabatan"
                            name="jabatan"
                            :value="old('jabatan', $pegawai->jabatan)"
                            :invalid="$errors->has('jabatan')"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('jabatan')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="id_dept" class="block text-sm font-medium text-gray-700">Departemen</label>
                        <select
                            id="id_dept"
                            name="id_dept"
                            @class([
                                'mt-1 block w-full rounded-md border px-3 py-2 text-sm text-gray-900 shadow-xs focus:ring-2 focus:outline-none',
                                'border-red-500 focus:border-red-600 focus:ring-red-500/30' => $errors->has('id_dept'),
                                'border-gray-300 focus:border-brand-600 focus:ring-brand-600/30' => ! $errors->has('id_dept'),
                            ])
                            @if ($errors->has('id_dept')) aria-invalid="true" @endif
                        >
                            <option value="">Pilih departemen</option>
                            @foreach ($departemen as $dept)
                                <option value="{{ $dept->id }}" @selected(old('id_dept', $pegawai->id_dept) == $dept->id)>{{ $dept->kode }} — {{ $dept->nama }}</option>
                            @endforeach
                        </select>
                        @error('id_dept')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="foto" class="block text-sm font-medium text-gray-700">Foto profil</label>
                    @if ($pegawai->foto)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($pegawai->foto) }}"
                            alt="Foto {{ $pegawai->nama }}"
                            class="mt-2 h-16 w-16 rounded-full object-cover"
                        >
                    @endif
                    <x-input
                        id="foto"
                        name="foto"
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        :invalid="$errors->has('foto')"
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-gray-600">Opsional. Kosongkan untuk mempertahankan foto saat ini. Format JPG/JPEG/PNG, maksimal 2 MB.</p>
                    @error('foto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan perubahan</x-button>
                    <a
                        href="{{ route('admin.pegawai.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
