<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pendewasaans', function (Blueprint $table) {
            $table->id('id_pendewasaan');
            $table->foreignId('id_peremajaan')->nullable()->constrained('peremajaans', 'id_peremajaan')->nullOnDelete();
            $table->foreignId('id_meja')->nullable()->constrained('mejas', 'id_meja')->nullOnDelete();
            $table->integer('tanaman_berhasil');
            $table->integer('tanaman_gagal');
            $table->date('tgl_awal_pendewasaan');
            $table->date('tgl_akhir_pendewasaan');
            $table->text('keterangan')->nullable();
            $table->enum('status_notif', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pendewasaans'); }
};
