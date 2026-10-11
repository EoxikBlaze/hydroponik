<?php

namespace Tests\Feature;

use App\Models\AlertLog;
use App\Models\Meja;
use App\Models\Semai;
use App\Models\Tanaman;
use App\Models\User;
use App\Models\WaSetting;
use App\Services\NotifWaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notifikasi WhatsApp dibatasi whitelist (permintaan):
 *   solenoid ON/OFF (auto dari ESP32 & perintah manual web),
 *   water_level Penuh/Kosong, jadwal H-1 pindah meja, dan pindah tanam.
 * Tipe lain (ph, tds, suhu, kelembapan, emergency) tetap masuk AlertLog
 * untuk riwayat web, tapi TIDAK dikirim ke WhatsApp.
 *
 * Kanal: Fonnte — pengaturan (API key & nomor) di tabel wa_settings.
 */
class NotifWaTipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.fonnte', [
            'endpoint' => 'https://api.fonnte.com/send',
            'types' => ['solenoid', 'water_level', 'jadwal', 'pindah_tanam'],
        ]);
        WaSetting::data()->update([
            'aktif' => true,
            'api_key' => 'TESTKEY',
            'target' => '081234567890',
        ]);
    }

    private function apiKey(): string
    {
        return config('services.esp32.key');
    }

    /* ---------------- WHITELIST: yang boleh masuk WA ---------------- */

    public function test_solenoid_on_dikirim_ke_whatsapp(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/notif-hardware', ['type' => 'solenoid', 'value' => 'ON'])
            ->assertOk();

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'api.fonnte.com/send')
                && str_contains($req['message'], 'SOLENOID');
        });
    }

    public function test_water_level_penuh_dikirim(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/notif-hardware', ['type' => 'water_level', 'value' => 'Penuh'])
            ->assertOk();

        Http::assertSentCount(1);
    }

    public function test_water_level_kurang_dikirim(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/notif-hardware', ['type' => 'water_level', 'value' => 'Kurang'])
            ->assertOk();

        Http::assertSentCount(1);
    }

    /* ---------------- YANG DIBUANG ---------------- */

    /** "Sedang" bukan peristiwa yang perlu diingatkan -> masuk log, TANPA WA. */
    public function test_water_level_sedang_tidak_dikirim_ke_whatsapp(): void
    {
        Http::fake();

        $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/notif-hardware', ['type' => 'water_level', 'value' => 'Sedang'])
            ->assertOk();

        Http::assertNothingSent();
        // Riwayat web tetap tercatat.
        $this->assertSame(1, AlertLog::where('type', 'water_level')->count());
    }

    /** Tipe sensor di luar whitelist: tercatat di AlertLog, TANPA WA. */
    public function test_tipe_sensor_luar_whitelist_tidak_dikirim(): void
    {
        foreach (['ph', 'tds', 'suhu_air', 'airTemp', 'humidity', 'emergency'] as $tipe) {
            Http::fake();
            $this->flushSession();

            $this->withHeader('X-API-KEY', $this->apiKey())
                ->postJson('/api/notif-hardware', ['type' => $tipe, 'value' => '9.9'])
                ->assertOk();

            Http::assertNothingSent();
            $this->assertSame(1, AlertLog::where('type', $tipe)->count(),
                "Riwayat {$tipe} harus tetap tercatat");
        }
    }

    public function test_filter_tolak_menyebut_alasan(): void
    {
        $wa = app(NotifWaService::class);

        $this->assertNotNull($wa->tolak('ph', '7.2'), 'ph harus ditolak');
        $this->assertNotNull($wa->tolak('water_level', 'Sedang'), 'Sedang harus ditolak');
        $this->assertNull($wa->tolak('solenoid', 'ON'), 'solenoid ON harus lolos');
        $this->assertNull($wa->tolak('water_level', 'Penuh'), 'Penuh harus lolos');
        $this->assertNull($wa->tolak('jadwal', 'H-1'), 'jadwal harus lolos');
    }

    public function test_format_pesan_membaca_status(): void
    {
        $wa = app(NotifWaService::class);

        $this->assertStringContainsString('ON (menyala)', $wa->formatPesan('solenoid', 'ON'));
        $this->assertStringContainsString('OFF (mati)', $wa->formatPesan('solenoid', 'OFF'));
        $this->assertStringContainsString('Penuh', $wa->formatPesan('water_level', 'Penuh'));
        $this->assertStringContainsString('Kosong', $wa->formatPesan('water_level', 'Kurang'));
        $this->assertStringContainsString('PINDAH MEJA', $wa->formatPesan('jadwal', 'H-1'));
    }

    /* ---------------- JADWAL H-1 ---------------- */

    private function buatJadwal(string $tglAkhir): void
    {
        $tanaman = Tanaman::first() ?? Tanaman::create(['nama_tanaman' => 'Selada']);
        $meja = Meja::first() ?? Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);

        Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => $meja->id_meja,
            'jumlah_benih' => 100, 'benih_berhasil' => 0, 'benih_gagal' => 0,
            'tgl_awal_semai' => now()->subDays(6)->toDateString(),
            'tgl_akhir_semai' => $tglAkhir, 'status' => 'proses',
        ]);
    }

    public function test_jadwal_h1_mengirim_dan_mendedup(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->buatJadwal(now()->addDay()->toDateString());

        $this->artisan('jadwal:h1')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(1, AlertLog::where('type', 'jadwal')->count());

        // Siklus berikutnya: kunci sama -> TIDAK kirim lagi.
        $this->artisan('jadwal:h1')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(1, AlertLog::where('type', 'jadwal')->count());
    }

    /** Beberapa jadwal berbeda pada hari yang sama harus semua terkirim
     *  DAN tidak saling menggeser dedup (bukan cuma baris terakhir). */
    public function test_beberapa_jadwal_hari_sama_tidak_saling_menggeser_dedup(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $besok = now()->addDay()->toDateString();
        foreach ([1, 2] as $i) {
            $this->buatJadwal($besok);
        }

        $this->artisan('jadwal:h1')->assertSuccessful();
        $this->assertSame(2, AlertLog::where('type', 'jadwal')->count(),
            'Tiap jadwal punya kunci sendiri');
        Http::assertSentCount(1);   // digabung jadi satu pesan (hemat rate limit)

        // Run ulang: keduanya sudah tercatat -> nihil.
        $this->artisan('jadwal:h1')->assertSuccessful();
        $this->assertSame(2, AlertLog::where('type', 'jadwal')->count());
        Http::assertSentCount(1);
    }

    /** WA belum aktif -> jadwal TIDAK dicatat supaya bisa retry nanti. */
    public function test_jadwal_h1_wa_matih_tidak_mengunci_dedup(): void
    {
        Http::fake();
        WaSetting::data()->update(['aktif' => false]);
        $this->buatJadwal(now()->addDay()->toDateString());

        $this->artisan('jadwal:h1')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, AlertLog::where('type', 'jadwal')->count(),
            'Tidak boleh terkunci saat WA mati');
    }

    public function test_jadwal_h1_kosong_tanpa_jadwal(): void
    {
        Http::fake();
        $this->artisan('jadwal:h1')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_jadwal_h1_dry_run_tidak_mengirim(): void
    {
        Http::fake();
        $this->buatJadwal(now()->addDay()->toDateString());

        $this->artisan('jadwal:h1', ['--dry-run' => true])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, AlertLog::where('type', 'jadwal')->count());
    }

    /* ---------------- FITUR BARU: solenoid manual & pindah tanam ---------------- */

    /** Perintah manual kran (Isi/Hentikan di halaman Water Control) -> WA. */
    public function test_solenoid_manual_fill_dikirim_ke_whatsapp(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $admin = User::factory()->create(['level' => 'admin']);
        $this->actingAs($admin)
            ->post('/watercontrol/fill')
            ->assertRedirect();

        $this->assertDatabaseHas('alert_logs', ['type' => 'solenoid', 'value' => 'ON (manual)']);
        Http::assertSent(function ($req) {
            return str_contains($req['message'], 'SOLENOID') && str_contains($req['message'], 'manual');
        });
    }

    /** Perintah manual stop -> WA (nilai OFF berbeda, tidak kena cooldown ON). */
    public function test_solenoid_manual_stop_dikirim_ke_whatsapp(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $admin = User::factory()->create(['level' => 'admin']);
        $this->actingAs($admin)
            ->post('/watercontrol/stop')
            ->assertRedirect();

        $this->assertDatabaseHas('alert_logs', ['type' => 'solenoid', 'value' => 'OFF (manual)']);
        Http::assertSent(fn ($req) => str_contains($req['message'], 'OFF'));
    }

    /** Pindah tanam (ganti tahap) -> WA + tercatat di AlertLog. */
    public function test_pindah_tanam_dikirim_ke_whatsapp(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $ok = app(NotifWaService::class)->catatDanKirim(
            'pindah_tanam',
            'Semai → Meja A (12 pindah)'
        );

        $this->assertTrue($ok);
        $this->assertDatabaseHas('alert_logs', ['type' => 'pindah_tanam']);
        Http::assertSent(function ($req) {
            return str_contains($req['message'], 'PINDAH TANAM') && str_contains($req['message'], 'Meja A');
        });
    }

    /** Pindah tanam tetap TERCATAT walaupun Fonnte belum aktif (tidak hilang). */
    public function test_pindah_tanam_tetap_tercatat_ketika_wa_nonaktif(): void
    {
        Http::fake();
        WaSetting::data()->update(['aktif' => false]);

        $ok = app(NotifWaService::class)->catatDanKirim('pindah_tanam', 'Tes nonaktif');

        $this->assertFalse($ok);
        $this->assertDatabaseHas('alert_logs', ['type' => 'pindah_tanam', 'value' => 'Tes nonaktif']);
        Http::assertNothingSent();
    }
}
