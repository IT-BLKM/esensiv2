<x-layouts.admin title="Ubah Cuti">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ubah jenis cuti</h1>
            <p class="mt-1 text-sm text-gray-600">Perbarui kode dan aturan {{ $cuti->nama }}.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.cuti.update', $cuti) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700">Kode cuti</label>
                    <x-input
                        id="kode"
                        name="kode"
                        :value="old('kode', $cuti->kode)"
                        :invalid="$errors->has('kode')"
                        maxlength="4"
                        class="mt-1"
                    />
                    @error('kode')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama cuti</label>
                    <x-input
                        id="nama"
                        name="nama"
                        :value="old('nama', $cuti->nama)"
                        :invalid="$errors->has('nama')"
                        maxlength="25"
                        class="mt-1"
                    />
                    @error('nama')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="maks_hari" class="block text-sm font-medium text-gray-700">Maksimal hari</label>
                        <x-input
                            id="maks_hari"
                            name="maks_hari"
                            type="number"
                            min="0"
                            :value="old('maks_hari', $cuti->maks_hari)"
                            :invalid="$errors->has('maks_hari')"
                            class="mt-1"
                        />
                        @error('maks_hari')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="kuota" class="block text-sm font-medium text-gray-700">Kuota</label>
                        <x-input
                            id="kuota"
                            name="kuota"
                            type="number"
                            min="0"
                            :value="old('kuota', $cuti->kuota)"
                            :invalid="$errors->has('kuota')"
                            class="mt-1"
                        />
                        @error('kuota')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="maks_bawa" class="block text-sm font-medium text-gray-700">Maksimal bawa</label>
                        <x-input
                            id="maks_bawa"
                            name="maks_bawa"
                            type="number"
                            min="0"
                            :value="old('maks_bawa', $cuti->maks_bawa)"
                            :invalid="$errors->has('maks_bawa')"
                            class="mt-1"
                        />
                        @error('maks_bawa')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan perubahan</x-button>
                    <a
                        href="{{ route('admin.cuti.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
