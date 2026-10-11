<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan notifikasi WhatsApp (Fonnte).
 *
 * SATU baris (id = 1). API key & nomor tujuan diisi lewat halaman web
 * (menu Notifikasi WhatsApp) — TIDAK lewat .env — supaya bisa diganti
 * tanpa akses ke server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('aktif')->default(false);
            $table->string('api_key', 100)->nullable();
            $table->string('target', 500)->nullable(); // nomor tujuan, dipisah koma
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_settings');
    }
};
