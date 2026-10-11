<?php

namespace Tests\Feature;

use App\Models\SensorThreshold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_halaman_ambang_sensor_tampil(): void
    {
        SensorThreshold::create(['sensor' => 'ph', 'min' => 5, 'max' => 7]);

        $this->actingAs($this->user)->get('/ambang')
            ->assertOk()
            ->assertSee('ph');
    }

    public function test_tambah_ambang_masuk_database(): void
    {
        $this->actingAs($this->user)->post('/ambang', [
            'sensor' => 'tds', 'min' => 50, 'max' => 1000,
        ])->assertRedirect('/ambang');

        $this->assertDatabaseHas('sensor_thresholds', ['sensor' => 'tds', 'min' => 50, 'max' => 1000]);
    }

    public function test_edit_ambang_memperbarui_database(): void
    {
        $t = SensorThreshold::create(['sensor' => 'ph', 'min' => 5, 'max' => 7]);

        $this->actingAs($this->user)->put("/ambang/{$t->id}", [
            'sensor' => 'ph', 'min' => 5.5, 'max' => 6.5,
        ])->assertRedirect('/ambang');

        $this->assertDatabaseHas('sensor_thresholds', ['sensor' => 'ph', 'min' => 5.5, 'max' => 6.5]);
    }

    public function test_hapus_ambang_menghapus_database(): void
    {
        $t = SensorThreshold::create(['sensor' => 'humidity', 'min' => 50, 'max' => 90]);

        $this->actingAs($this->user)->delete("/ambang/{$t->id}")->assertRedirect('/ambang');

        $this->assertDatabaseMissing('sensor_thresholds', ['sensor' => 'humidity']);
    }

    public function test_ambang_max_tidak_boleh_kurang_dari_min(): void
    {
        $this->actingAs($this->user)->post('/ambang', [
            'sensor' => 'airTemp', 'min' => 40, 'max' => 10,
        ])->assertSessionHasErrors('max');

        $this->assertDatabaseMissing('sensor_thresholds', ['sensor' => 'airTemp']);
    }

    public function test_ambang_sensor_tidak_boleh_duplikat(): void
    {
        SensorThreshold::create(['sensor' => 'ph', 'min' => 5, 'max' => 7]);

        $this->actingAs($this->user)->post('/ambang', [
            'sensor' => 'ph', 'min' => 6, 'max' => 8,
        ])->assertSessionHasErrors('sensor');

        $this->assertSame(1, SensorThreshold::where('sensor', 'ph')->count());
    }

    public function test_ambang_sensor_harus_jenis_yang_dikenal_alat(): void
    {
        $this->actingAs($this->user)->post('/ambang', [
            'sensor' => 'sensor_ngawur', 'min' => 1, 'max' => 2,
        ])->assertSessionHasErrors('sensor');
    }

    public function test_api_esp32_menerima_key_benar(): void
    {
        config(['services.esp32.key' => 'kunci_uji']);
        SensorThreshold::create(['sensor' => 'ph', 'min' => 5, 'max' => 7]);

        $this->getJson('/api/get-thresholds', ['X-API-KEY' => 'kunci_uji'])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['thresholds' => ['ph']]);
    }

    public function test_api_esp32_menolak_key_salah(): void
    {
        config(['services.esp32.key' => 'kunci_uji']);

        $this->getJson('/api/get-thresholds', ['X-API-KEY' => 'kunci-salah'])
            ->assertStatus(403);
    }

    public function test_user_level_petani_bisa_dibuat(): void
    {
        $this->actingAs($this->user)->post('/user', [
            'username' => 'asep',
            'email' => 'asep@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'level' => 'petani',
        ])->assertRedirect('/user');

        $this->assertDatabaseHas('users', ['username' => 'asep', 'level' => 'petani']);
    }

    public function test_user_level_ngawur_ditolak(): void
    {
        $this->actingAs($this->user)->post('/user', [
            'username' => 'badu',
            'email' => 'badu@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'level' => 'sultan',
        ])->assertSessionHasErrors('level');
    }
}
