<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tunjangan_tingkat', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 32);
            $table->string('level', 16);
            $table->unsignedSmallInteger('min');
            $table->unsignedSmallInteger('max')->nullable();
            $table->decimal('persen', 5, 2);
            $table->timestamps();

            $table->unique(['kategori', 'level']);
            $table->index(['kategori', 'min']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tunjangan_tingkat');
    }
};
