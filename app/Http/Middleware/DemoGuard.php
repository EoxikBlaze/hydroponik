<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengaman akun demo:
 * - hanya boleh MELIHAT data real dan MENAMBAH data sementara (auto-hapus 1 jam)
 * - dilarang edit/hapus data, kelola user, ganti profil, dan kontrol kran (hardware asli)
 */
class DemoGuard
{
    /** Satu-satunya aksi tulis yang diizinkan untuk akun demo (tambah data sementara) */
    private const BOLEH_TAMBAH = [
        'meja.store', 'tanaman.store', 'siklus.store', 'semai.store',
        'peremajaan.store', 'pendewasaan.store', 'panen.store',
        'tanam.mulai', 'tanam.selesai',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Bersihkan data demo kedaluwarsa (dibatasi 1x per menit agar ringan)
        if (Cache::add('demo_purge_lock', 1, 60)) {
            Artisan::call('demo:purge');
        }

        $user = $request->user();
        if (! $user || ! $user->is_demo) {
            return $next($request);
        }

        $nama = (string) ($request->route()?->getName() ?? '');
        $method = $request->method();

        $ditolak =
            str_starts_with($nama, 'user.') ||
            in_array($nama, ['profil.update', 'profil.password.update', 'watercontrol.fill', 'watercontrol.stop', 'watercontrol.mode'], true) ||
            in_array($method, ['PUT', 'PATCH', 'DELETE'], true) ||
            ($method === 'POST' && ! in_array($nama, self::BOLEH_TAMBAH, true));

        if ($ditolak) {
            return redirect()->back()->with(
                'error',
                'Akun demo: aksi ini dinonaktifkan. Anda hanya dapat menambah data sementara (otomatis terhapus maksimal 1 jam).'
            );
        }

        return $next($request);
    }
}
