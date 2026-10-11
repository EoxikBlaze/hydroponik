<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiap fase tanam punya meja sendiri (semai di tray, peremajaan & pendewasaan pindah meja).
 * Semai sebelumnya tidak menyimpan meja — tambahkan supaya batch terbedakan per meja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semais', function (Blueprint $table) {
            $table->foreignId('id_meja')
                ->nullable()
                ->after('id_siklus')
                ->constrained('mejas', 'id_meja')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('semais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_meja');
        });
    }
};
