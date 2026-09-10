<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('peremajaans', function (Blueprint $table) {
            $table->id('id_peremajaan');
            $table->foreignId('id_semai')->nullable()->constrained('semais', 'id_semai')->nullOnDelete();
            $table->foreignId('id_meja')->nullable()->constrained('mejas', 'id_meja')->nullOnDelete();
            $table->integer('benih_berhasil');
            $table->integer('benih_gagal');
            $table->date('tgl_awal_peremajaan');
            $table->date('tgl_akhir_peremajaan');
            $table->text('keterangan')->nullable();
            $table->enum('status_notif', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('peremajaans'); }
};
