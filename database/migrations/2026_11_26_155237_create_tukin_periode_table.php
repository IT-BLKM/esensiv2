<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tukin_periode', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->boolean('terkunci')->default(true);
            $table->foreignId('dikunci_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->dateTime('dikunci_pada')->nullable();
            $table->foreignId('dibuka_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->dateTime('dibuka_pada')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukin_periode');
    }
};
