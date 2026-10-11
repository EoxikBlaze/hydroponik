<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan hasil audit database:
 *
 * 1. CHECK constraint semais.status ('proses','selesai','gagal') HILANG — korban
 *    rebuild tabel SQLite saat migration 000002 memakai ->after(). Pulihkan kembali.
 *    (Di MySQL constraint ini tidak pernah hilang; langkah rebuild dilewati.)
 *
 * 2. Tabel rantai data tidak punya INDEX sama sekali — setiap join & agregasi
 *    kumulatif (laporan, kartu Tanam Saya) full-scan. Tambahkan index pada kolom FK.
 *
 * Migrasi berjalan TANPA transaksi karena PRAGMA foreign_keys tidak efektif
 * di dalam transaksi SQLite.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->pulihkanCheckStatusSqlite();
        }

        Schema::table('semais', function (Blueprint $table) {
            $table->index('id_tanaman');
            $table->index('id_siklus');
            $table->index('id_meja');
        });
        Schema::table('peremajaans', function (Blueprint $table) {
            $table->index('id_semai');
            $table->index('id_meja');
        });
        Schema::table('pendewasaans', function (Blueprint $table) {
            $table->index('id_peremajaan');
            $table->index('id_meja');
        });
        Schema::table('panens', function (Blueprint $table) {
            $table->index('id_pendewasaan');
        });
    }

    public function down(): void
    {
        foreach ([
            'semais' => ['id_tanaman', 'id_siklus', 'id_meja'],
            'peremajaans' => ['id_semai', 'id_meja'],
            'pendewasaans' => ['id_peremajaan', 'id_meja'],
            'panens' => ['id_pendewasaan'],
        ] as $tabel => $kolom) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom) {
                foreach ($kolom as $k) {
                    $table->dropIndex([$k]);
                }
            });
        }
        // CHECK status sengaja tidak dihapus — constraint tambahan tidak merusak.
    }

    /** Rebuild semais dengan CHECK status dikembalikan (satu-satunya cara di SQLite). */
    private function pulihkanCheckStatusSqlite(): void
    {
        $kolom = '"id_semai","id_tanaman","id_siklus","id_meja","jumlah_benih","tgl_awal_semai",'
            .'"tgl_akhir_semai","status","keterangan","benih_berhasil","benih_gagal",'
            .'"created_at","updated_at","expires_at"';

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement(<<<'SQL'
            CREATE TABLE "semais_baru" (
                "id_semai" integer primary key autoincrement not null,
                "id_tanaman" integer,
                "id_siklus" integer,
                "id_meja" integer,
                "jumlah_benih" integer not null,
                "tgl_awal_semai" date not null,
                "tgl_akhir_semai" date not null,
                "status" varchar not null default ('proses') check ("status" in ('proses', 'selesai', 'gagal')),
                "keterangan" text,
                "benih_berhasil" integer not null default ('0'),
                "benih_gagal" integer not null default ('0'),
                "created_at" datetime,
                "updated_at" datetime,
                "expires_at" datetime,
                foreign key("id_tanaman") references "tanamen"("id_tanaman") on delete set null,
                foreign key("id_siklus") references "sikluses"("id_siklus") on delete set null,
                foreign key("id_meja") references "mejas"("id_meja") on delete set null
            )
            SQL);
        DB::statement("INSERT INTO \"semais_baru\" ({$kolom}) SELECT {$kolom} FROM \"semais\"");
        DB::statement('DROP TABLE "semais"');
        DB::statement('ALTER TABLE "semais_baru" RENAME TO "semais"');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
