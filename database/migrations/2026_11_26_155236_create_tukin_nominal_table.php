<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tukin_nominal', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan', 255)->unique();
            $table->unsignedBigInteger('nominal');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukin_nominal');
    }
};
