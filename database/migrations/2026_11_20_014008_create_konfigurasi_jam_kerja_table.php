<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('konfigurasi_jam_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai');
            $table->unsignedBigInteger('id_jam_kerja');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $table->timestamps();

            $table->unique(['id_pegawai', 'hari']);
            $table->index('id_jam_kerja');
        });

        // FK dipasang hanya jika tabel referensinya sudah tersedia.
        // `pegawai` masih dikerjakan di issue #12, `jam_kerja` di issue #10.
        if (Schema::hasTable('pegawai')) {
            Schema::table('konfigurasi_jam_kerja', function (Blueprint $table) {
                $table->foreign('id_pegawai')->references('id')->on('pegawai')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('jam_kerja')) {
            Schema::table('konfigurasi_jam_kerja', function (Blueprint $table) {
                $table->foreign('id_jam_kerja')->references('id')->on('jam_kerja')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_jam_kerja');
    }
};
