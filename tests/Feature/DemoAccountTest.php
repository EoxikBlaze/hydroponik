<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\User;
use App\Models\WaterControl;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountTest extends TestCase
{
    use RefreshDatabase;

    private function akunDemo(): User
    {
        return User::factory()->create(['is_demo' => true]);
    }

    public function test_data_demo_ditandai_sementara_1_jam(): void
    {
        $this->actingAs($this->akunDemo())->post('/meja', [
            'meja' => 'Meja Demo', 'jumlah_lubang' => 12,
        ])->assertRedirect(route('meja.index'));

        $meja = Meja::where('meja', 'Meja Demo')->firstOrFail();
        $this->assertNotNull($meja->expires_at);
        $this->assertEqualsWithDelta(
            now()->addHour()->timestamp,
            Carbon::parse($meja->expires_at)->timestamp,
            10
        );
    }

    public function test_data_user_biasa_permanen(): void
    {
        $this->actingAs(User::factory()->create(['is_demo' => false]))->post('/meja', [
            'meja' => 'Meja Normal', 'jumlah_lubang' => 12,
        ])->assertRedirect(route('meja.index'));

        $this->assertNull(Meja::where('meja', 'Meja Normal')->firstOrFail()->expires_at);
    }

    public function test_akun_demo_tidak_bisa_edit_dan_hapus(): void
    {
        $demo = $this->akunDemo();
        $meja = Meja::create(['meja' => 'Meja Asli', 'jumlah_lubang' => 10]);

        $this->actingAs($demo)->put("/meja/{$meja->id_meja}", [
            'meja' => 'Diubah Demo', 'jumlah_lubang' => 5,
        ])->assertSessionHas('error');

        $this->actingAs($demo)->delete("/meja/{$meja->id_meja}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('mejas', ['id_meja' => $meja->id_meja, 'meja' => 'Meja Asli']);
    }

    public function test_akun_demo_tidak_bisa_kelola_user(): void
    {
        $demo = $this->akunDemo();

        $this->actingAs($demo)->get('/user')->assertSessionHas('error');

        $this->actingAs($demo)->post('/user', [
            'username' => 'hacker', 'email' => 'x@x.id',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'level' => 'admin',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'hacker']);
    }

    public function test_akun_demo_tidak_bisa_kontrol_kran(): void
    {
        $demo = $this->akunDemo();
        WaterControl::current();

        $this->actingAs($demo)->post('/watercontrol/fill')->assertSessionHas('error');

        $this->assertSame('AUTO', WaterControl::current()->command);
    }

    public function test_demo_purge_hapus_hanya_data_kedaluwarsa(): void
    {
        $kadaluarsa = Meja::create(['meja' => 'Meja Expired', 'jumlah_lubang' => 5]);
        $kadaluarsa->expires_at = now()->subMinute();
        $kadaluarsa->save();

        $tetap = Meja::create(['meja' => 'Meja Normal', 'jumlah_lubang' => 5]);

        $this->artisan('demo:purge')->assertSuccessful();

        $this->assertDatabaseMissing('mejas', ['id_meja' => $kadaluarsa->id_meja]);
        $this->assertDatabaseHas('mejas', ['id_meja' => $tetap->id_meja]);
    }

    public function test_data_kedaluwarsa_tersembunyi_dari_daftar(): void
    {
        $meja = Meja::create(['meja' => 'Meja Expired', 'jumlah_lubang' => 5]);
        $meja->expires_at = now()->subMinute();
        $meja->save();

        $this->assertFalse(Meja::where('meja', 'Meja Expired')->exists());
    }
}
