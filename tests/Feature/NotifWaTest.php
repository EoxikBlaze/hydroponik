<?php

namespace Tests\Feature;

use App\Models\AlertLog;
use App\Models\User;
use App\Models\WaSetting;
use App\Services\NotifWaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Jalur notifikasi WhatsApp (Fonnte — API key diatur lewat website).
 * Kunci tiga hal: (1) alert tetap tercatat meski WA gagal/belum disetel,
 * (2) request Fonnte hanya keluar saat aktif & memakai parameter benar,
 * (3) API key bisa disimpan dari halaman web tanpa membocorkan token lama.
 */
class NotifWaTest extends TestCase
{
    use RefreshDatabase;

    /** Siapkan pengaturan WA aktif di database (pengganti isian .env). */
    private function setAktif(array $ubah = []): WaSetting
    {
        $s = WaSetting::data();
        $s->update(array_merge([
            'aktif' => true,
            'api_key' => 'TESTKEY123',
            'target' => '081234567890',
        ], $ubah));

        return $s->refresh();
    }

    private function apiKey(): string
    {
        return config('services.esp32.key');
    }

    public function test_alert_tetap_tersimpan_ketika_wa_belum_disetel(): void
    {
        Http::fake();

        $resp = $this->withHeaders(['X-API-KEY' => $this->apiKey()])
            ->postJson('/api/notif-hardware', ['type' => 'water_level', 'value' => 'Kurang']);

        $resp->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('alert_logs', ['type' => 'water_level', 'value' => 'Kurang']);
        Http::assertNothingSent();
    }

    public function test_format_pesan_alert(): void
    {
        $wa = app(NotifWaService::class);
        $p = $wa->formatPesan('emergency', 'Solenoid nyala 7 menit');

        $this->assertStringContainsString('HarvestHouse', $p);
        $this->assertStringContainsString('EMERGENCY', $p);
        $this->assertStringContainsString('Solenoid nyala 7 menit', $p);
    }

    public function test_aktif_hanya_saat_pengaturan_lengkap(): void
    {
        $wa = app(NotifWaService::class);

        $this->setAktif(['aktif' => false, 'api_key' => 'abc', 'target' => '628123']);
        $this->assertFalse($wa->aktif(), 'nonaktif -> tidak aktif');

        $this->setAktif(['aktif' => true, 'api_key' => '', 'target' => '628123']);
        $this->assertFalse($wa->aktif(), 'api_key kosong -> tidak aktif');

        $this->setAktif(['aktif' => true, 'api_key' => 'abc', 'target' => '']);
        $this->assertFalse($wa->aktif(), 'target kosong -> tidak aktif');

        $this->setAktif(['aktif' => true, 'api_key' => 'abc', 'target' => '628123']);
        $this->assertTrue($wa->aktif(), 'lengkap -> aktif');
    }

    public function test_kirim_memakai_parameter_fonnte(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->setAktif(['api_key' => 'TOKEN123', 'target' => '6281234567890']);

        $ok = app(NotifWaService::class)->kirim('Tes pesan dari HarvestHouse');

        $this->assertTrue($ok);
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'api.fonnte.com/send')
                && $req->header('Authorization') === ['TOKEN123']
                && $req['target'] === '6281234567890'
                && str_contains($req['message'], 'HarvestHouse');
        });
    }

    /**
     * Fonnte membalas HTTP 200 tapi "status":false (token salah / device
     * putus) -> harus dianggap GAGAL, bukan terkirim.
     */
    public function test_fonnte_status_false_dianggap_gagal(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'invalid token'], 200)]);
        $this->setAktif();

        $this->assertFalse(app(NotifWaService::class)->kirim('Tes'));
    }

    /**
     * Alert dari firmware memicu kirim WA — asal tipenya di whitelist
     * (solenoid / water_level Penuh-Kosong / jadwal).
     *
     * CATATAN spesifikasi: 'emergency' tidak dikirim ke WA
     * (lihat NotifWaTipeTest) — tetap tercatat di AlertLog.
     */
    public function test_api_alert_memicu_kirim_wa(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->setAktif();

        $this->withHeaders(['X-API-KEY' => $this->apiKey()])
            ->postJson('/api/notif-hardware', ['type' => 'solenoid', 'value' => 'ON'])
            ->assertOk();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.fonnte.com/send'));
        $this->assertSame(1, AlertLog::where('type', 'solenoid')->count());
    }

    /** Tipe di luar whitelist: tetap tercatat, tapi TIDAK kirim WA. */
    public function test_alert_luar_whitelist_tidak_dikirim_tapi_tetap_tercatat(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->setAktif();

        $this->withHeaders(['X-API-KEY' => $this->apiKey()])
            ->postJson('/api/notif-hardware', ['type' => 'emergency', 'value' => 'Tes darurat'])
            ->assertOk();

        Http::assertNothingSent();
        $this->assertSame(1, AlertLog::where('type', 'emergency')->count());
    }

    public function test_kirim_wa_tidak_menggagalkan_api_bila_gagal(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response('Error', 500)]);
        $this->setAktif();

        // API tetap OK & alert tetap tersimpan walau Fonnte 500
        $this->withHeaders(['X-API-KEY' => $this->apiKey()])
            ->postJson('/api/notif-hardware', ['type' => 'tds', 'value' => '0'])
            ->assertOk();

        $this->assertDatabaseHas('alert_logs', ['type' => 'tds', 'value' => '0']);
    }

    /* ---------------- PENGATURAN LEWAT WEBSITE ---------------- */

    public function test_halaman_pengaturan_butuh_login(): void
    {
        $this->get('/notifikasi-wa')->assertRedirect(route('login'));
    }

    public function test_halaman_pengaturan_menyimpan_api_key_dari_website(): void
    {
        $admin = User::factory()->create(['level' => 'admin']);

        $this->actingAs($admin)->get('/notifikasi-wa')
            ->assertOk()
            ->assertSee('API Key Fonnte')
            ->assertSee('Cara Mendapatkan API Key Fonnte');

        $this->actingAs($admin)->post('/notifikasi-wa', [
            'aktif' => '1',
            'api_key' => 'TOKEN123ABC',
            'target' => '081234567890, 089876543210',
        ])->assertRedirect(route('notifikasi-wa.index'));

        $this->assertDatabaseHas('wa_settings', [
            'id' => 1,
            'aktif' => true,
            'api_key' => 'TOKEN123ABC',
            'target' => '081234567890, 089876543210',
        ]);

        // Token asli TIDAK boleh bocor ke halaman — hanya 4 karakter terakhir.
        $this->actingAs($admin)->get('/notifikasi-wa')
            ->assertOk()
            ->assertSee('3ABC')
            ->assertDontSee('TOKEN123ABC');
    }

    public function test_api_key_kosong_mempertahankan_nilai_lama(): void
    {
        $admin = User::factory()->create(['level' => 'admin']);
        $this->setAktif(['api_key' => 'TOKENLAMA123', 'target' => '081234567890']);

        $this->actingAs($admin)->post('/notifikasi-wa', [
            'aktif' => '1',
            'api_key' => '',
            'target' => '089998887777',
        ])->assertRedirect(route('notifikasi-wa.index'));

        $this->assertDatabaseHas('wa_settings', [
            'id' => 1,
            'api_key' => 'TOKENLAMA123',
            'target' => '089998887777',
        ]);
    }

    public function test_aktif_tanpa_api_key_ditolak(): void
    {
        $admin = User::factory()->create(['level' => 'admin']);

        $this->actingAs($admin)->post('/notifikasi-wa', [
            'aktif' => '1',
            'api_key' => '',
            'target' => '081234567890',
        ])->assertSessionHasErrors('api_key');

        $this->assertFalse(WaSetting::data()->aktif, 'tidak boleh aktif tanpa API key');
    }

    public function test_uji_kirim_mengirim_pesan_uji(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $admin = User::factory()->create(['level' => 'admin']);
        $this->setAktif();

        $this->actingAs($admin)->post('/notifikasi-wa/uji')
            ->assertRedirect(route('notifikasi-wa.index'));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.fonnte.com/send')
            && str_contains($r['message'], 'PESAN UJI'));
    }

    public function test_uji_kirim_ditolak_saat_belum_aktif(): void
    {
        Http::fake();
        $admin = User::factory()->create(['level' => 'admin']);

        $this->actingAs($admin)->post('/notifikasi-wa/uji')
            ->assertRedirect(route('notifikasi-wa.index'));

        Http::assertNothingSent();
    }
}
