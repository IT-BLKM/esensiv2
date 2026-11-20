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
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai');
            $table->unsignedBigInteger('id_jam_kerja')->nullable();
            $table->unsignedBigInteger('id_pengajuan')->nullable();
            $table->date('tgl_presensi');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->string('foto_masuk', 255)->nullable();
            $table->string('foto_keluar', 255)->nullable();
            $table->string('koordinat_masuk', 255)->nullable();
            $table->string('koordinat_keluar', 255)->nullable();
            $table->enum('status', ['hadir', 'cuti', 'sakit', 'dl']);
            $table->timestamps();

            $table->unique(['tgl_presensi', 'id_pegawai']);
            $table->index('id_jam_kerja');
            $table->index('id_pengajuan');
        });

        // FK dipasang hanya jika tabel referensinya sudah tersedia.
        // `pegawai` di PR #59, `jam_kerja` di PR #57, `pengajuan` di PR #61.
        if (Schema::hasTable('pegawai')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->foreign('id_pegawai')->references('id')->on('pegawai')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('jam_kerja')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->foreign('id_jam_kerja')->references('id')->on('jam_kerja')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pengajuan')) {
            Schema::table('presensi', function (Blueprint $table) {
                $table->foreign('id_pengajuan')->references('id')->on('pengajuan')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
