<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('panens', function (Blueprint $table) {
            $table->id('id_panen');
            $table->foreignId('id_pendewasaan')->constrained('pendewasaans', 'id_pendewasaan')->cascadeOnDelete();
            $table->integer('panen_berhasil')->default(0);
            $table->integer('panen_gagal')->default(0);
            $table->date('tgl_panen');
            $table->text('keterangan')->nullable();
            $table->enum('status_notif', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('panens'); }
};
