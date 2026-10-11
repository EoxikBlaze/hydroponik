<?php

namespace Tests\Feature;

use App\Models\AlertLog;
use App\Models\User;
use App\Models\WaterControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kontrol kran (Water Control):
 *   - "Mode Kerja Sistem" harus redirect kembali ke halaman dengan pesan sukses,
 *     BUKAN menampilkan JSON mentah {"status":true} di browser;
 *   - nilai mode tersimpan benar di tabel water_controls;
 *   - perintah manual Buka/Tutup kran juga redirect + mencatat notifikasi WA.
 */
class WaterControlModeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['level' => 'admin']);
    }

    public function test_simpan_mode_mengembalikan_redirect_bukan_json(): void
    {
        $resp = $this->actingAs($this->admin())
            ->post('/watercontrol/setMode', ['mode' => 'MANUAL']);

        // Regresi: dulu return response()->json(['status' => true]) sehingga
        // browser menampilkan teks mentah {"status":true}.
        $resp->assertRedirect();
        $this->assertStringNotContainsString('{"status":true}', (string) $resp->getContent());
        $resp->assertSessionHas('success');

        $this->assertDatabaseHas('water_controls', ['mode' => 'MANUAL']);
    }

    public function test_mode_auto_tersimpan(): void
    {
        WaterControl::current()->update(['mode' => 'MANUAL']);

        $this->actingAs($this->admin())
            ->post('/watercontrol/setMode', ['mode' => 'AUTO'])
            ->assertRedirect();

        $this->assertDatabaseHas('water_controls', ['mode' => 'AUTO']);
    }

    public function test_mode_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->from('/watercontrol')
            ->post('/watercontrol/setMode', ['mode' => 'BALADA'])
            ->assertRedirect('/watercontrol')
            ->assertSessionHasErrors('mode');
    }

    public function test_buka_tutup_kran_redirect_dengan_pesan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/watercontrol/fill')
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('water_controls', ['command' => 'FORCE_FILL']);

        $this->actingAs($admin)
            ->post('/watercontrol/stop')
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('water_controls', ['command' => 'FORCE_STOP']);
    }

    /* ---------------- SEMANTIK MODE (tanpa flash ESP32) ---------------- */

    private function apiKey(): string
    {
        return config('services.esp32.key');
    }

    /** Pilih MANUAL -> kran langsung ditutup + alat keluar dari isi-otomatis. */
    public function test_pilih_manual_langsung_tutup_kran(): void
    {
        $this->actingAs($this->admin())
            ->post('/watercontrol/setMode', ['mode' => 'MANUAL'])
            ->assertRedirect();

        $this->assertDatabaseHas('water_controls', ['mode' => 'MANUAL', 'command' => 'FORCE_STOP']);
    }

    /** Penjaga: di mode MANUAL, kran yang dibuka TANPA perintah pengguna ditutup paksa. */
    public function test_penjaga_manual_menutup_kran_otomatis(): void
    {
        WaterControl::current()->update(['mode' => 'MANUAL', 'command' => 'AUTO']);
        AlertLog::simpan('solenoid', 'ON');   // event otomatis dari alat (bukan tombol user)

        $resp = $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/save-sensor', ['solenoid_state' => 'ON']);

        $resp->assertOk()->assertJsonPath('command', 'FORCE_STOP');
    }

    /** Sesi manual pengguna (baru tekan Buka Kran) tidak diganggu penjaga. */
    public function test_sesi_manual_pengguna_tidak_diganggu(): void
    {
        WaterControl::current()->update(['mode' => 'MANUAL', 'command' => 'AUTO']);
        AlertLog::simpan('solenoid', 'ON (manual)');   // pengguna yang membuka

        $resp = $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/save-sensor', ['solenoid_state' => 'ON']);

        $resp->assertOk()->assertJsonPath('command', 'AUTO');
    }

    /** Mode AUTO: server tidak ikut campur (murni logika alat). */
    public function test_mode_auto_tidak_ikut_campur(): void
    {
        WaterControl::current()->update(['mode' => 'AUTO', 'command' => 'AUTO']);
        AlertLog::simpan('solenoid', 'ON');

        $resp = $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/save-sensor', ['solenoid_state' => 'ON']);

        $resp->assertOk()->assertJsonPath('command', 'AUTO');
    }

    /** Kran manual yang lupa ditutup > 20 menit ikut ditutup penjaga. */
    public function test_kran_manual_lewat_20_menit_ditutup(): void
    {
        WaterControl::current()->update(['mode' => 'MANUAL', 'command' => 'AUTO']);
        $lama = AlertLog::simpan('solenoid', 'ON (manual)');
        $lama->created_at = now()->subMinutes(25);   // sudah lewat batas
        $lama->save();

        $resp = $this->withHeader('X-API-KEY', $this->apiKey())
            ->postJson('/api/save-sensor', ['solenoid_state' => 'ON']);

        $resp->assertOk()->assertJsonPath('command', 'FORCE_STOP');
    }
}
