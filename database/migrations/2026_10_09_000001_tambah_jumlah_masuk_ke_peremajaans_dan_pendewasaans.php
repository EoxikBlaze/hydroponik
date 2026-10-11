<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom jumlah_masuk pada peremajaans & pendewasaans.
 *
 * KENAPA: sebelumnya batas tiap tahap diambil dari induk
 * (mis. panen dibatasi benih_berhasil milik PEREMAJAAN, bukan per baris).
 * Saat satu tahap dipecah ke BEBERAPA meja (split 400 -> 200 + 200),
 * tiap baris jadi tidak punya batas sendiri -> panen bisa kelebihan catat.
 *
 * jumlah_masuk = berapa benih/tanaman yang MASUK ke baris ini saat
 * perpindahan tahap. Dipakai sebagai batas pada tahap berikutnya.
 *
 * Baris LAMA bernilai NULL -> fallback ke batas induk (perilaku lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peremajaans', function (Blueprint $table) {
            if (! Schema::hasColumn('peremajaans', 'jumlah_masuk')) {
                $table->integer('jumlah_masuk')->nullable()->after('id_meja');
            }
        });

        Schema::table('pendewasaans', function (Blueprint $table) {
            if (! Schema::hasColumn('pendewasaans', 'jumlah_masuk')) {
                $table->integer('jumlah_masuk')->nullable()->after('id_meja');
            }
        });
    }

    public function down(): void
    {
        Schema::table('peremajaans', function (Blueprint $table) {
            if (Schema::hasColumn('peremajaans', 'jumlah_masuk')) {
                $table->dropColumn('jumlah_masuk');
            }
        });

        Schema::table('pendewasaans', function (Blueprint $table) {
            if (Schema::hasColumn('pendewasaans', 'jumlah_masuk')) {
                $table->dropColumn('jumlah_masuk');
            }
        });
    }
};
