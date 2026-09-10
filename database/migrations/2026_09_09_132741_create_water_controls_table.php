<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('water_controls', function (Blueprint $table) {
            $table->id();
            $table->enum('command', ['AUTO', 'FORCE_FILL', 'FORCE_STOP'])->default('AUTO');
            $table->enum('mode', ['AUTO', 'MANUAL'])->default('AUTO');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }
    public function down(): void { Schema::dropIfExists('water_controls'); }
};
