<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurTanamanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Rantai induk: meja -> tanaman -> siklus -> semai (selesai) -> peremajaan -> pendewasaan.
     *
     * @return array{semai: Semai, peremajaan: Peremajaan, pendewasaan: Pendewasaan}
     */
    private function siapkanRantai(): array
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $tanaman = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);
        $siklus = Siklus::create(['id_tanaman' => $tanaman->id_tanaman, 'nama_siklus' => 'Semai', 'waktu_siklus' => 14]);
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_siklus' => $siklus->id_siklus,
            'jumlah_benih' => 100, 'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-15',
            'status' => 'selesai', 'benih_berhasil' => 80, 'benih_gagal' => 10,
        ]);
        $peremajaan = Peremajaan::create([
            'id_semai' => $semai->id_semai, 'id_meja' => $meja->id_meja,
            'benih_berhasil' => 50, 'benih_gagal' => 5,
            'tgl_awal_peremajaan' => '2026-10-16', 'tgl_akhir_peremajaan' => '2026-10-30',
        ]);
        $pendewasaan = Pendewasaan::create([
            'id_peremajaan' => $peremajaan->id_peremajaan, 'id_meja' => $meja->id_meja,
            'tanaman_berhasil' => 40, 'tanaman_gagal' => 5,
            'tgl_awal_pendewasaan' => '2026-10-31', 'tgl_akhir_pendewasaan' => '2026-11-20',
        ]);

        return ['semai' => $semai, 'peremajaan' => $peremajaan, 'pendewasaan' => $pendewasaan];
    }

    public function test_alur_lengkap_semai_sampai_panen(): void
    {
        $r = $this->siapkanRantai();

        $this->actingAs($this->user)->post('/panen', [
            'id_pendewasaan' => $r['pendewasaan']->id_pendewasaan,
            'panen_berhasil' => 30, 'panen_gagal' => 5, 'tgl_panen' => '2026-11-25',
        ])->assertRedirect(route('panen.index'));

        $this->assertDatabaseHas('panens', ['id_pendewasaan' => $r['pendewasaan']->id_pendewasaan, 'panen_berhasil' => 30]);
    }

    public function test_siklus_harus_milik_tanaman_yang_sama(): void
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $selada = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);
        $bayam = Tanaman::create(['nama_tanaman' => 'Bayam', 'id_meja' => $meja->id_meja]);
        $siklusBayam = Siklus::create(['id_tanaman' => $bayam->id_tanaman, 'nama_siklus' => 'Semai', 'waktu_siklus' => 14]);

        $this->actingAs($this->user)->post('/semai', [
            'id_tanaman' => $selada->id_tanaman,
            'id_siklus' => $siklusBayam->id_siklus,
            'jumlah_benih' => 10, 'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-15',
        ])->assertSessionHasErrors('id_siklus');

        $this->assertDatabaseCount('semais', 0);
    }

    public function test_tanggal_akhir_semai_tidak_boleh_sebelum_awal(): void
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $selada = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);

        $this->actingAs($this->user)->post('/semai', [
            'id_tanaman' => $selada->id_tanaman,
            'jumlah_benih' => 10, 'tgl_awal_semai' => '2026-10-15', 'tgl_akhir_semai' => '2026-10-01',
        ])->assertSessionHasErrors('tgl_akhir_semai');
    }

    public function test_hasil_semai_tidak_boleh_melebihi_jumlah_benih(): void
    {
        $r = $this->siapkanRantai();

        $this->actingAs($this->user)->put("/semai/{$r['semai']->id_semai}", [
            'id_tanaman' => $r['semai']->id_tanaman,
            'jumlah_benih' => 100,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-15',
            'status' => 'selesai',
            'benih_berhasil' => 90, 'benih_gagal' => 30,   // 120 > 100
        ])->assertSessionHasErrors('benih_gagal');
    }

    public function test_peremajaan_hanya_dari_semai_selesai(): void
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $selada = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);
        $semai = Semai::create([
            'id_tanaman' => $selada->id_tanaman, 'jumlah_benih' => 100,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-15',
            'status' => 'proses',
        ]);

        $this->actingAs($this->user)->post('/peremajaan', [
            'id_semai' => $semai->id_semai, 'id_meja' => $meja->id_meja,
            'benih_berhasil' => 10, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-10-16', 'tgl_akhir_peremajaan' => '2026-10-30',
        ])->assertSessionHasErrors('id_semai');

        $this->assertDatabaseCount('peremajaans', 0);
    }

    public function test_peremajaan_kumulatif_dibatasi_hasil_semai(): void
    {
        $r = $this->siapkanRantai();   // 55 terpakai dari 80 hasil semai -> sisa 25

        $this->actingAs($this->user)->post('/peremajaan', [
            'id_semai' => $r['semai']->id_semai,
            'benih_berhasil' => 25, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-11-01', 'tgl_akhir_peremajaan' => '2026-11-15',
        ])->assertRedirect(route('peremajaan.index'));   // pas sisa = boleh

        $this->actingAs($this->user)->post('/peremajaan', [
            'id_semai' => $r['semai']->id_semai,
            'benih_berhasil' => 1, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-11-01', 'tgl_akhir_peremajaan' => '2026-11-15',
        ])->assertSessionHasErrors('benih_berhasil');   // 81 > 80 = tolak
    }

    public function test_pendewasaan_tidak_boleh_melebihi_hasil_peremajaan(): void
    {
        // hasil peremajaan: 50 benih berhasil, sudah terpakai 45 oleh fixture -> sisa 5
        $r = $this->siapkanRantai();

        $this->actingAs($this->user)->post('/pendewasaan', [
            'id_peremajaan' => $r['peremajaan']->id_peremajaan,
            'tanaman_berhasil' => 3, 'tanaman_gagal' => 3,   // 6 + 45 = 51 > 50
            'tgl_awal_pendewasaan' => '2026-12-01', 'tgl_akhir_pendewasaan' => '2026-12-20',
        ])->assertSessionHasErrors('tanaman_berhasil');

        // pas di batas: 5 + 45 = 50 = boleh
        $this->actingAs($this->user)->post('/pendewasaan', [
            'id_peremajaan' => $r['peremajaan']->id_peremajaan,
            'tanaman_berhasil' => 4, 'tanaman_gagal' => 1,
            'tgl_awal_pendewasaan' => '2026-12-01', 'tgl_akhir_pendewasaan' => '2026-12-20',
        ])->assertRedirect(route('pendewasaan.index'));
    }

    public function test_panen_tidak_boleh_melebihi_hasil_pendewasaan(): void
    {
        $r = $this->siapkanRantai();   // hasil pendewasaan: 40 tanaman berhasil

        $this->actingAs($this->user)->post('/panen', [
            'id_pendewasaan' => $r['pendewasaan']->id_pendewasaan,
            'panen_berhasil' => 35, 'panen_gagal' => 10,   // 45 > 40
            'tgl_panen' => '2026-11-25',
        ])->assertSessionHasErrors('panen_berhasil');

        $this->assertDatabaseCount('panens', 0);
    }

    public function test_update_turunan_tidak_menghitung_dirinya_sendiri(): void
    {
        $r = $this->siapkanRantai();   // peremajaan #1: 50+5 = 55 dari 80

        $this->actingAs($this->user)->put("/peremajaan/{$r['peremajaan']->id_peremajaan}", [
            'id_semai' => $r['semai']->id_semai,
            'benih_berhasil' => 75, 'benih_gagal' => 5,   // 80 == 80 = boleh (diri sendiri dikecualikan)
            'tgl_awal_peremajaan' => '2026-10-16', 'tgl_akhir_peremajaan' => '2026-10-30',
        ])->assertRedirect(route('peremajaan.index'));

        $this->assertDatabaseHas('peremajaans', ['id_peremajaan' => $r['peremajaan']->id_peremajaan, 'benih_berhasil' => 75]);
    }

    public function test_id_induk_wajib_ada_di_master(): void
    {
        $this->actingAs($this->user)->post('/peremajaan', [
            'id_semai' => 999,   // tidak ada
            'benih_berhasil' => 1, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-10-16', 'tgl_akhir_peremajaan' => '2026-10-30',
        ])->assertSessionHasErrors('id_semai');

        $this->actingAs($this->user)->post('/panen', [
            'id_pendewasaan' => 999,
            'panen_berhasil' => 1, 'panen_gagal' => 0, 'tgl_panen' => '2026-11-25',
        ])->assertSessionHasErrors('id_pendewasaan');
    }

    public function test_form_tambah_menampilkan_label_master_data(): void
    {
        $this->siapkanRantai();

        $resp = $this->actingAs($this->user)->get('/pendewasaan/create');
        $resp->assertOk();
        $resp->assertSee('Selada');          // nama tanaman dari master data
        $resp->assertSee('Meja A');          // nama meja dari master data

        $resp = $this->actingAs($this->user)->get('/panen/create');
        $resp->assertOk();
        $resp->assertSee('Selada');
    }
}
