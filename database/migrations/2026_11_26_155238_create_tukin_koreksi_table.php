<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tukin_koreksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pegawai')->constrained('pegawai')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->decimal('persen', 6, 2);
            $table->string('alasan', 255);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['id_pegawai', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukin_koreksi');
    }
};
