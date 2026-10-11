<?php

namespace Tests\Feature;

use App\Models\AlertLog;
use App\Models\Monitoring;
use App\Models\User;
use App\Models\WaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Ringkasan sensor pagi -> WhatsApp:
 *   - rata-rata24 jam (suhu air, pH, TDS, suhu udara, kelembapan),
 *   - distribusi level air + jumlah ON/OFF solenoid,
 *   - target kirim jam07:00 WITA, tepat sekali sehari,
 *   - kirim gagal TIDAK dicatat -> boleh dicoba lagi (retry).
 */
class RingkasanPagiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        WaSetting::data()->update([
            'aktif' => true,
            'api_key' => 'TESTKEY',
            'target' => '081234567890',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Atur jam internal aplikasi (dipakai guard07:00 WITA). */
    private function jam(string $waktu): void
    {
        Carbon::setTestNow(Carbon::parse($waktu, 'Asia/Makassar'));
    }

    /** Dua bacaan sensor contoh -> rata2:27.0 °C,5.90 pH,750 ppm. */
    private function isiSensor(): void
    {
        $kini = now();
        Monitoring::query()->insert([
            ['waterTemp' => 28.0, 'ph' => 6.0, 'tds' => 700, 'airTemp' => 30.0, 'humidity' => 70.0, 'water_level' => 'Penuh', 'created_at' => $kini->subHours(3)],
            ['waterTemp' => 26.0, 'ph' => 5.8, 'tds' => 800, 'airTemp' => 32.0, 'humidity' => 60.0, 'water_level' => 'Sedang', 'created_at' => $kini],
        ]);
    }

    public function test_kirim_rata_rata_pagi_tepat_jam_07(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->jam('2026-10-11 07:05:00');
        $this->isiSensor();

        Artisan::call('sensor:ringkasan');

        Http::assertSent(function ($req) {
            return str_contains($req['message'], 'RINGKASAN SENSOR')
                && str_contains($req['message'], '27.0')      // rata2 suhu air
                && str_contains($req['message'], '750')       // rata2 TDS
                && str_contains($req['message'], 'Penuh');    // distribusi level
        });
        $this->assertDatabaseHas('alert_logs', ['type' => 'ringkasan', 'value' => 'ringkasan|2026-10-11']);
    }

    public function test_belum_jam_07_tidak_dikirim(): void
    {
        Http::fake();
        $this->jam('2026-10-11 06:59:00');
        $this->isiSensor();

        Artisan::call('sensor:ringkasan');

        Http::assertNothingSent();
    }

    public function test_tepat_sekali_per_hari(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->jam('2026-10-11 07:10:00');
        $this->isiSensor();

        Artisan::call('sensor:ringkasan');
        Artisan::call('sensor:ringkasan');   // run berikutnya di hari sama

        Http::assertSentCount(1);
        $this->assertSame(1, AlertLog::where('type', 'ringkasan')->count());
    }

    public function test_kirim_gagal_tidak_dicatat_dan_bisa_retry(): void
    {
        // Fake bertingkat: gagal dulu, baru berhasil — tanpa ganti fake di tengah tes.
        $berhasil = false;
        Http::fake(['api.fonnte.com/*' => function () use (&$berhasil) {
            return Http::response(['status' => $berhasil], 200);
        }]);
        $this->jam('2026-10-11 07:20:00');
        $this->isiSensor();

        Artisan::call('sensor:ringkasan');

        // gagal -> kunci TIDAK dicatat -> siklus berikutnya boleh coba lagi
        $this->assertDatabaseMissing('alert_logs', ['type' => 'ringkasan']);

        $berhasil = true;
        Artisan::call('sensor:ringkasan');

        $this->assertDatabaseHas('alert_logs', ['type' => 'ringkasan', 'value' => 'ringkasan|2026-10-11']);
    }
}
