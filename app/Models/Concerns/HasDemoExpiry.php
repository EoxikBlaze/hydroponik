<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Tandai data buatan akun demo sebagai sementara (kadaluarsa 1 jam).
 *
 * - creating  : otomatis isi expires_at = sekarang + 1 jam untuk akun demo
 * - global    : data yang sudah lewat waktunya disembunyikan dari semua tampilan
 */
trait HasDemoExpiry
{
    protected static function bootHasDemoExpiry(): void
    {
        // Sembunyikan data demo yang sudah kedaluwarsa dari semua query tampilan.
        //
        // PENTING: kolom harus dikualifikasi nama tabel (`semais.expires_at`).
        // Versi lama menulis `expires_at` polos — aman saat query tanpa join,
        // tapi meledak jadi "ambiguous column name" begitu ada join ke tabel
        // lain yang juga punya kolom expires_at (semua tabel di rantai siklus
        // memilikinya). Dipakai getModel()->getTable() supaya berlaku untuk
        // semua model pemakai trait ini.
        static::addGlobalScope('demo_expirasi', function (Builder $builder) {
            $kolom = $builder->getModel()->getTable().'.expires_at';

            $builder->where(function (Builder $q) use ($kolom) {
                $q->whereNull($kolom)->orWhere($kolom, '>', now());
            });
        });

        // Data yang dibuat akun demo otomatis sementara (maksimal 1 jam)
        static::creating(function (Model $model) {
            if (Auth::check() && Auth::user()->is_demo) {
                $model->expires_at = now()->addHour();
            }
        });
    }
}
