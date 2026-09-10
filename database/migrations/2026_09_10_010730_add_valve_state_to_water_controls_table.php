<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('water_controls', function (Blueprint $table) {
            $table->enum('valve_state', ['ON', 'OFF'])->default('OFF')->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('water_controls', function (Blueprint $table) {
            $table->dropColumn('valve_state');
        });
    }
};
