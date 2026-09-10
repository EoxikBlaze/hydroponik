<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('semais', function (Blueprint $table) {
            $table->id('id_semai');
            $table->foreignId('id_tanaman')->nullable()->constrained('tanamen', 'id_tanaman')->nullOnDelete();
            $table->foreignId('id_siklus')->nullable()->constrained('sikluses', 'id_siklus')->nullOnDelete();
            $table->integer('jumlah_benih');
            $table->date('tgl_awal_semai');
            $table->date('tgl_akhir_semai');
            $table->enum('status', ['proses', 'selesai', 'gagal'])->default('proses');
            $table->text('keterangan')->nullable();
            $table->integer('benih_berhasil')->default(0);
            $table->integer('benih_gagal')->default(0);
            $table->enum('status_notif', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('semais'); }
};
