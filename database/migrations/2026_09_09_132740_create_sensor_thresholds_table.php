<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sensor_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('sensor', 50)->nullable();
            $table->float('min')->nullable();
            $table->float('max')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }
    public function down(): void { Schema::dropIfExists('sensor_thresholds'); }
};
