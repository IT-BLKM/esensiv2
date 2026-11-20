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
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('id_dept');
            $table->string('nip', 18)->unique();
            $table->string('nama', 255);
            $table->string('jabatan', 255);
            $table->string('foto', 255)->nullable();
            $table->string('no_hp', 15);
            $table->timestamps();

            $table->index('id_dept');
        });

        // FK dipasang hanya jika tabel referensinya sudah tersedia.
        // `user` masih di PR #54, `departemen` masih di PR #55.
        if (Schema::hasTable('user')) {
            Schema::table('pegawai', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('user')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('departemen')) {
            Schema::table('pegawai', function (Blueprint $table) {
                $table->foreign('id_dept')->references('id')->on('departemen')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
