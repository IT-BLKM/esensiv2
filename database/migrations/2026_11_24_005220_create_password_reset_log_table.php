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
        Schema::create('password_reset_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('id_pegawai');
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_pegawai');
            $table->foreign('admin_id')->references('id')->on('user')->cascadeOnDelete();
            $table->foreign('id_pegawai')->references('id')->on('pegawai')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_log');
    }
};
