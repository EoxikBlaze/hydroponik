<?php

namespace App\Http\Controllers;

use App\Models\WaSetting;
use App\Services\NotifWaService;
use Illuminate\Http\Request;

/**
 * Pengaturan notifikasi WhatsApp (Fonnte) — API key, nomor tujuan,
 * dan tombol kirim pesan uji, semuanya diatur dari website.
 */
class NotifWaController extends Controller
{
    public function index()
    {
        return view('notifikasi.index', ['set' => WaSetting::data()]);
    }

    public function update(Request $request)
    {
        $set = WaSetting::data();

        $data = $request->validate([
            'aktif' => 'nullable',
            'api_key' => 'nullable|string|max:100',
            'target' => 'nullable|string|max:500',
        ]);

        $aktif = $request->boolean('aktif');
        $apiBaru = trim((string) ($data['api_key'] ?? ''));
        $target = trim((string) ($data['target'] ?? ''));

        // Kosong = pertahankan yang lama (input API key sengaja tidak menampilkan
        // token asli, jadi simpan selalu "jangan diubah" bila dikosongkan).
        $apiFinal = $apiBaru !== '' ? $apiBaru : (string) $set->api_key;

        if ($aktif && $apiFinal === '') {
            return back()
                ->withErrors(['api_key' => 'API key Fonnte wajib diisi untuk mengaktifkan notifikasi.'])
                ->withInput();
        }

        if ($aktif && $target === '') {
            return back()
                ->withErrors(['target' => 'Nomor tujuan wajib diisi untuk mengaktifkan notifikasi.'])
                ->withInput();
        }

        // Nomor pribadi (08xx / 62xx / +62xx) atau ID grup WhatsApp (xxx@g.us).
        if ($target !== '' && ! preg_match('/^[0-9A-Za-z@._+(),\s\-]+$/', $target)) {
            return back()
                ->withErrors(['target' => 'Format nomor tujuan tidak valid — pisahkan antar nomor dengan tanda koma.'])
                ->withInput();
        }

        $set->update([
            'aktif' => $aktif,
            'api_key' => $apiFinal,
            'target' => $target,
        ]);

        return redirect()->route('notifikasi-wa.index')
            ->with('success', 'Pengaturan notifikasi WhatsApp tersimpan.');
    }

    /** Tombol "Kirim Pesan Uji" — buktikan pengaturan sudah jalan. */
    public function uji()
    {
        $wa = app(NotifWaService::class);

        if (! $wa->aktif()) {
            return redirect()->route('notifikasi-wa.index')->with(
                'error',
                'Notifikasi belum aktif — isi API key & nomor tujuan, centang "Aktifkan", simpan, lalu uji lagi.'
            );
        }

        $ok = $wa->kirim($wa->pesanUji());

        return $ok
            ? redirect()->route('notifikasi-wa.index')->with('success', 'Pesan uji terkirim — cek WhatsApp di nomor tujuan.')
            : redirect()->route('notifikasi-wa.index')->with(
                'error',
                'Gagal mengirim pesan uji. Periksa API key, pastikan device di dashboard Fonnte berstatus tersambung (detail di storage/logs/laravel.log).'
            );
    }
}
