<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:create {email} {--nama=Administrator} {--password=}')]
#[Description('Buat atau reset akun admin tanpa akun default bawaan')]
class BuatAdmin extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email admin tidak valid.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password admin (minimal 12 karakter)');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('Password admin minimal 12 karakter.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null && $user->role !== 'admin') {
            $this->error('Email ini sudah dipakai akun non-admin. Pilih email lain.');

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'nama' => (string) ($this->option('nama') ?: 'Administrator'),
                'password' => $password,
                'role' => 'admin',
            ],
        );

        $this->info(($user === null ? 'Admin dibuat' : 'Password admin diperbarui').': '.$email);

        return self::SUCCESS;
    }
}
