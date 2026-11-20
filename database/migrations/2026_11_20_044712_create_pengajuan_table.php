<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHECK_NAME = 'pengajuan_jenis_cuti_check';

    /**
     * Jenis='cuti' wajib punya id_cuti; sakit/dl wajib tanpa id_cuti.
     */
    private function checkExpression(): string
    {
        return "((jenis = 'cuti' AND id_cuti IS NOT NULL) OR (jenis IN ('sakit', 'dl') AND id_cuti IS NULL))";
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Laravel 13 belum menyediakan builder CHECK constraint, jadi dibuat
        // dengan SQL mentah yang sesuai driver database.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak bisa ALTER TABLE ADD CONSTRAINT. FK ditanam bersama
            // CHECK langsung di CREATE TABLE: kalau FK ditambahkan lewat
            // Schema::table(), SQLite akan rebuild tabel dan CHECK hilang.
            $this->createSqliteTable();
        } else {
            $this->createSqlTable();
            $this->addForeignKeys();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }

    /**
     * FK dipasang hanya jika tabel referensinya sudah tersedia.
     * `pegawai` ada di PR #59, `cuti` di PR #60, `user` di PR #54.
     */
    private function addForeignKeys(): void
    {
        if (Schema::hasTable('pegawai')) {
            Schema::table('pengajuan', function (Blueprint $table) {
                $table->foreign('id_pegawai')->references('id')->on('pegawai')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('cuti')) {
            Schema::table('pengajuan', function (Blueprint $table) {
                // RESTRICT, bukan SET NULL: MySQL menolak referential action
                // pada kolom yang juga dipakai CHECK constraint (error 3823).
                $table->foreign('id_cuti')->references('id')->on('cuti')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('user')) {
            Schema::table('pengajuan', function (Blueprint $table) {
                $table->foreign('disetujui_oleh')->references('id')->on('user')->nullOnDelete();
            });
        }
    }

    /**
     * MySQL/MariaDB: tabel dibuat Blueprint lalu CHECK ditambahkan lewat ALTER.
     */
    private function createSqlTable(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai');
            $table->unsignedBigInteger('id_cuti')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->enum('jenis', ['cuti', 'sakit', 'dl']);
            $table->string('keterangan', 255);
            $table->string('lampiran', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->unsignedBigInteger('disetujui_oleh')->nullable();
            $table->dateTime('disetujui_pada')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index('id_pegawai');
            $table->index('status');
        });

        DB::statement(sprintf(
            'ALTER TABLE pengajuan ADD CONSTRAINT %s CHECK %s',
            self::CHECK_NAME,
            $this->checkExpression()
        ));
    }

    /**
     * SQLite: CHECK dan FK ditanam di CREATE TABLE supaya tidak dihapus
     * ketika skema di-rebuild.
     */
    private function createSqliteTable(): void
    {
        $foreignKeys = [];

        if (Schema::hasTable('pegawai')) {
            $foreignKeys[] = 'CONSTRAINT pengajuan_id_pegawai_foreign FOREIGN KEY (id_pegawai) REFERENCES pegawai (id) ON DELETE CASCADE';
        }

        if (Schema::hasTable('cuti')) {
            $foreignKeys[] = 'CONSTRAINT pengajuan_id_cuti_foreign FOREIGN KEY (id_cuti) REFERENCES cuti (id) ON DELETE RESTRICT';
        }

        if (Schema::hasTable('user')) {
            $foreignKeys[] = 'CONSTRAINT pengajuan_disetujui_oleh_foreign FOREIGN KEY (disetujui_oleh) REFERENCES "user" (id) ON DELETE SET NULL';
        }

        DB::statement(sprintf('
            CREATE TABLE pengajuan (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                id_pegawai INTEGER NOT NULL,
                id_cuti INTEGER NULL,
                tanggal_mulai DATE NOT NULL,
                tanggal_berakhir DATE NOT NULL,
                jenis TEXT NOT NULL,
                keterangan VARCHAR(255) NOT NULL,
                lampiran VARCHAR(255) NULL,
                status TEXT NOT NULL,
                disetujui_oleh INTEGER NULL,
                disetujui_pada DATETIME NULL,
                catatan VARCHAR(255) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                CONSTRAINT %s CHECK %s%s
            )', self::CHECK_NAME, $this->checkExpression(), $foreignKeys === [] ? '' : ','.PHP_EOL.implode(','.PHP_EOL, $foreignKeys)));

        DB::statement('CREATE INDEX pengajuan_id_pegawai_index ON pengajuan (id_pegawai)');
        DB::statement('CREATE INDEX pengajuan_status_index ON pengajuan (status)');
    }
};
