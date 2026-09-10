<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sikluses', function (Blueprint $table) {
            $table->id('id_siklus');
            $table->foreignId('id_tanaman')->nullable()->constrained('tanamen', 'id_tanaman')->nullOnDelete();
            $table->string('nama_siklus', 100);
            $table->integer('waktu_siklus');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sikluses'); }
};
