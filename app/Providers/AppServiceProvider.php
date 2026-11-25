<?php

namespace App\Providers;

use App\Models\Pegawai;
use App\Policies\PegawaiPolicy;
use App\Services\Hashing\LegacyAwareHasher;
use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Laporan belum punya model; subjeknya adalah Pegawai yang datanya
         * dilaporkan. PegawaiPolicy mewarisi ability view/viewAny dari
         * LaporanPolicy dan menambah ability managePegawai untuk CRUD admin.
         * Policy lain tetap auto-discovered lewat konvensi nama.
         */
        Gate::policy(Pegawai::class, PegawaiPolicy::class);

        /*
         * Driver Argon2id + verifikasi bcrypt lama. Hash baru selalu
         * Argon2id; hash bcrypt dari aplikasi lama tetap bisa login dan
         * akan di-rehash oleh AuthController setelah login berhasil.
         */
        Hash::extend('argon2id_bcrypt', function ($app): LegacyAwareHasher {
            return new LegacyAwareHasher(
                new Argon2IdHasher($app['config']->get('hashing.argon', [])),
                new BcryptHasher($app['config']->get('hashing.bcrypt', [])),
            );
        });
    }
}
