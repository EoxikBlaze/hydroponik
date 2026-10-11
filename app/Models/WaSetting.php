<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan notifikasi WhatsApp (Fonnte) — SATU baris (id = 1).
 * Diisi dari halaman web /notifikasi-wa, bukan dari .env.
 */
class WaSetting extends Model
{
    protected $table = 'wa_settings';

    protected $fillable = ['aktif', 'api_key', 'target'];

    protected $casts = ['aktif' => 'boolean'];

    /** Ambil baris pengaturan (dibuat otomatis dengan nilai bawaan bila belum ada). */
    public static function data(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }

    /** Nomor tujuan dalam bentuk array (sudah dibersihkan & tanpa entri kosong). */
    public function daftarTarget(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->target))));
    }
}
