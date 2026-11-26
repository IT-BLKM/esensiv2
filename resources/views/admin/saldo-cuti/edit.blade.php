<x-layouts.admin title="Atur Saldo Cuti">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Atur saldo awal cuti</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $pegawai->nama }} · {{ $pegawai->nip }}
            </p>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card title="Ringkasan {{ $tahun }}" subtitle="Dihitung dari kuota + tabungan - pemakaian yang sudah tercatat.">
            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                <div class="rounded-md bg-gray-50 px-3 py-2">
                    <dt class="text-xs text-gray-600">Kuota {{ $tahun }}</dt>
                    <dd class="font-semibold text-gray-900">{{ $ringkas['kuota'] }} hari</dd>
                </div>
                <div class="rounded-md bg-gray-50 px-3 py-2">
                    <dt class="text-xs text-gray-600">Terpakai</dt>
                    <dd class="font-semibold text-gray-900">{{ $ringkas['terpakai'] }} hari</dd>
                </div>
                <div class="rounded-md bg-gray-50 px-3 py-2">
                    <dt class="text-xs text-gray-600">Sisa kuota</dt>
                    <dd class="font-semibold text-gray-900">{{ $ringkas['sisaKuota'] }} hari</dd>
                </div>
                <div class="rounded-md bg-gray-50 px-3 py-2">
                    <dt class="text-xs text-gray-600">Total tersedia</dt>
                    <dd class="font-semibold text-gray-900">{{ $ringkas['totalTersedia'] }} hari</dd>
                </div>
                <div class="rounded-md bg-gray-50 px-3 py-2">
                    <dt class="text-xs text-gray-600">Maks bawa {{ $tahun + 1 }}</dt>
                    <dd class="font-semibold text-gray-900">{{ $ringkas['maksBawaTahunDepan'] }} hari</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Tabungan cuti" subtitle="Isi sesuai saldo dari sistem lama. Maksimal {{ $cuti->maks_bawa }} hari per tahun sumber.">
            <form method="POST" action="{{ route('admin.saldo-cuti.update', $pegawai) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="saldo_dua_tahun_lalu" class="block text-sm font-medium text-gray-700">
                            Tabungan {{ $duaTahunLalu }}
                        </label>
                        <x-input
                            id="saldo_dua_tahun_lalu"
                            name="saldo_dua_tahun_lalu"
                            type="number"
                            min="0"
                            :max="$cuti->maks_bawa"
                            :value="old('saldo_dua_tahun_lalu', $saldoDuaTahunLalu)"
                            :invalid="$errors->has('saldo_dua_tahun_lalu')"
                            class="mt-1"
                        />
                        @error('saldo_dua_tahun_lalu')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="saldo_tahun_lalu" class="block text-sm font-medium text-gray-700">
                            Tabungan {{ $tahunLalu }}
                        </label>
                        <x-input
                            id="saldo_tahun_lalu"
                            name="saldo_tahun_lalu"
                            type="number"
                            min="0"
                            :max="$cuti->maks_bawa"
                            :value="old('saldo_tahun_lalu', $saldoTahunLalu)"
                            :invalid="$errors->has('saldo_tahun_lalu')"
                            class="mt-1"
                        />
                        @error('saldo_tahun_lalu')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <p class="text-xs text-gray-500">
                    Angka ini hanya tabungan. Pemakaian {{ $tahun }} tidak diubah dari halaman ini;
                    gunakan menu Pengajuan untuk mencatat cuti/sakit/DL historis.
                </p>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan saldo</x-button>
                    <a
                        href="{{ route('admin.saldo-cuti.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
