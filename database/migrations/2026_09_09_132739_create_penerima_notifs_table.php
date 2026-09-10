<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('penerima_notifs', function (Blueprint $table) {
            $table->id('id_penerima_notif');
            $table->string('nama', 100);
            $table->string('no_hp', 20);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('penerima_notifs'); }
};
