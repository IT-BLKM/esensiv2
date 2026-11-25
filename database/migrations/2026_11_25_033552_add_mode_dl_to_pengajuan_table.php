<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode Dinas Luar (issue lanjutan #36/#39):
     * - full    : sehari penuh, tidak perlu presensi; hari kerja dibuatkan
     *             baris presensi berstatus dl saat approval.
     * - setengah: hanya perlu absen sekali pada hari itu, tanpa absen pulang.
     *
     * Baris lama bernilai NULL dan diperlakukan sebagai `full` oleh kode.
     */
    public function up(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->enum('mode_dl', ['full', 'setengah'])->nullable()->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->dropColumn('mode_dl');
        });
    }
};
