<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi akun petani hanya ke menu lapangan sederhana:
 * Tanam Saya, Dashboard, Sensor Real-Time, dan Profil.
 * Menu lain (master data, siklus detail, laporan, kran, user) untuk admin/pengelola.
 */
class PetaniGuard
{
    private const BOLEH = ['tanam', 'dashboard', 'monitoring', 'profil', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->level === 'petani') {
            $nama = (string) ($request->route()?->getName() ?? '');
            $boleh = false;
            foreach (self::BOLEH as $awalan) {
                if ($nama === $awalan || str_starts_with($nama, $awalan.'.')) {
                    $boleh = true;
                    break;
                }
            }
            if (! $boleh) {
                return redirect()->route('tanam.index')->with(
                    'error',
                    'Menu ini untuk pengelola. Silakan gunakan menu "Tanam Saya".'
                );
            }
        }

        return $next($request);
    }
}
