<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur akun demo:
     * - users.is_demo       : menandai akun demo (data yang dibuatnya bersifat sementara)
     * - expires_at di 7 tabel data: waktu kadaluarsa data buatan akun demo (maks 1 jam)
     */
    private array $tabelData = [
        'mejas', 'tanamen', 'sikluses', 'semais',
        'peremajaans', 'pendewasaans', 'panens',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('level');
        });

        foreach ($this->tabelData as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });

        foreach ($this->tabelData as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
