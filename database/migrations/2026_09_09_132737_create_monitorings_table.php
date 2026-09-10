<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->float('waterTemp')->nullable();
            $table->float('ph')->nullable();
            $table->float('tds')->nullable();
            $table->float('airTemp')->nullable();
            $table->float('humidity')->nullable();
            $table->string('water_level', 20)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('monitorings'); }
};
