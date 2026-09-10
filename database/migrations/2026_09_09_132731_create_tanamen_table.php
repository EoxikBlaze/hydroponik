<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tanamen', function (Blueprint $table) {
            $table->id('id_tanaman');
            $table->string('nama_tanaman', 50);
            $table->foreignId('id_meja')->nullable()->constrained('mejas', 'id_meja')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tanamen'); }
};
