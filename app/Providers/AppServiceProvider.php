<?php

namespace App\Providers;

use App\Models\Pegawai;
use App\Policies\LaporanPolicy;
use Illuminate\Support\Facades\Gate;
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
         * dilaporkan. Policy lain tetap auto-discovered lewat konvensi nama.
         */
        Gate::policy(Pegawai::class, LaporanPolicy::class);
    }
}
