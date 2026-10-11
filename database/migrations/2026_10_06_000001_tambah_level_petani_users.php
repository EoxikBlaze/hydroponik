<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tambah level 'petani' untuk akun lapangan (alur sederhana di /tanam). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('level', ['admin', 'user', 'petani'])->default('user')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('level', ['admin', 'user'])->default('user')->change();
        });
    }
};
