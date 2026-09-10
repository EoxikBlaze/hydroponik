<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('value', 100);
            $table->enum('status', ['pending', 'sent'])->default('pending');
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('alert_logs'); }
};
