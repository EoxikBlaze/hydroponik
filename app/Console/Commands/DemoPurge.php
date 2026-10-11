<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('demo:purge')]
#[Description('Hapus data sementara buatan akun demo yang sudah lewat 1 jam')]
class DemoPurge extends Command
{
    /** Urutan anak -> induk agar aman terhadap foreign key */
    private const TABELS = [
        'panens', 'pendewasaans', 'peremajaans', 'semais',
        'sikluses', 'tanamen', 'mejas',
    ];

    public function handle(): int
    {
        $total = 0;
        foreach (self::TABELS as $tabel) {
            $total += DB::table($tabel)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();
        }

        $this->info("demo:purge selesai — {$total} baris data sementara dihapus.");

        return self::SUCCESS;
    }
}
