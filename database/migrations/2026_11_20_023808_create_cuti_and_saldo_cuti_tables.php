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
        Schema::create('cuti', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 4)->unique();
            $table->string('nama', 25);
            $table->integer('maks_hari')->nullable();
            $table->integer('kuota')->nullable();
            $table->integer('maks_bawa');
            $table->timestamps();
        });

        Schema::create('saldo_cuti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai');
            $table->foreignId('id_cuti')->constrained('cuti')->cascadeOnDelete();
            $table->integer('tahun_sumber');
            $table->integer('saldo');
            $table->timestamps();

            $table->unique(['id_pegawai', 'id_cuti', 'tahun_sumber']);
            $table->index('id_pegawai');
        });

        // FK ke pegawai dipasang hanya jika tabelnya sudah tersedia.
        // `pegawai` masih di PR #59, jadi migrate:fresh tetap hijau sebelum PR itu di-merge.
        if (Schema::hasTable('pegawai')) {
            Schema::table('saldo_cuti', function (Blueprint $table) {
                $table->foreign('id_pegawai')->references('id')->on('pegawai')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_cuti');
        Schema::dropIfExists('cuti');
    }
};
