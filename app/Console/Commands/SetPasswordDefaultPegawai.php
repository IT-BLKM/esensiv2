<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

#[Signature('pegawai:set-password-default
    {--force : Jalankan tanpa konfirmasi}')]
#[Description('Set password awal semua akun pegawai ke config auth.default_pegawai_password')]
class SetPasswordDefaultPegawai extends Command
{
    /**
     * Dipakai saat go-live setelah ETL. Akun admin tidak disentuh.
     */
    public function handle(): int
    {
        $password = (string) config('auth.default_pegawai_password', '123456');

        if (! $this->option('force')
            && ! $this->confirm("Set password SEMUA akun pegawai menjadi \"{$password}\"?", false)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $diubah = 0;
        $dilewati = 0;

        User::query()
            ->where('role', 'pegawai')
            ->chunkById(100, function ($daftarUser) use ($password, &$diubah, &$dilewati): void {
                foreach ($daftarUser as $user) {
                    if (Hash::check($password, $user->password)) {
                        $dilewati++;

                        continue;
                    }

                    $user->update(['password' => Hash::make($password)]);
                    $diubah++;
                }
            });

        Log::warning('Bulk set password default pegawai dijalankan.', [
            'diubah' => $diubah,
            'dilewati' => $dilewati,
        ]);

        $this->info("Selesai: {$diubah} akun diubah, {$dilewati} akun sudah memakai password default.");

        return self::SUCCESS;
    }
}
