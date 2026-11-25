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
        Schema::create('penggantian_keterlambatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_presensi')
                ->unique()
                ->constrained('presensi')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->time('target_pulang')->nullable();
            $table->time('batas_pulang')->nullable();
            $table->unsignedSmallInteger('menit_terlambat');
            $table->unsignedSmallInteger('menit_diganti')->default(0);
            $table->boolean('bisa_diganti')->default(true);
            $table->boolean('terpenuhi')->default(false);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggantian_keterlambatan');
    }
};
