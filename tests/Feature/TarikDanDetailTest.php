<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\Monitoring;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur tarik data & detail batch:
 *
 * 1) IKON MATA — GET /semai/batch/{id} mengembalikan seluruh jejak siklus
 *    satu batch (Semai -> Peremajaan -> Pendewasaan -> Panen) sebagai HTML
 *    fragmen yang ditempel ke modal. Termasuk sub-baris hasil split.
 *
 * 2) TARIK DATA SIKLUS — GET /siklus/csv dulu tidak ada; halaman Master
 *    Siklus kini punya tombol tarik CSV.
 *
 * 3) TARIK DATA SENSOR DIPAGINASI — tampilan awalnya memuat semua baris
 *    (ratusan) dalam satu layar; kini dibatasi per halaman. CSV tetap harus
 *    mengeluarkan SELURUH rentang, tidak ikut terpotong paginasi.
 */
class TarikDanDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tanaman $tanaman;

    private Meja $meja;

    private Semai $semai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['level' => 'admin']);
        $this->tanaman = Tanaman::create(['nama_tanaman' => 'Selada']);
        $this->meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 200]);

        $siklus = Siklus::create([
            'id_tanaman' => $this->tanaman->id_tanaman,
            'nama_siklus' => 'Siklus Uji',
            'waktu_siklus' => 20,
        ]);

        $this->semai = Semai::create([
            'id_tanaman' => $this->tanaman->id_tanaman,
            'id_siklus' => $siklus->id_siklus,
            'id_meja' => $this->meja->id_meja,
            'jumlah_benih' => 40,
            'tgl_awal_semai' => '2026-10-01',
            'tgl_akhir_semai' => '2026-10-06',
            'benih_berhasil' => 40,
            'benih_gagal' => 0,
        ]);
    }

    /** 1) Endpoint ikon mata menampilkan seluruh tahap batch. */
    public function test_detail_batch_menampilkan_seluruh_tahap(): void
    {
        $html = $this->actingAs($this->admin)
            ->get("/semai/batch/{$this->semai->id_semai}")
            ->assertOk()
            ->getContent();

        // Identitas batch
        $this->assertStringContainsString("Batch <strong>#{$this->semai->id_semai}</strong>", $html);
        $this->assertStringContainsString('Selada', $html);

        // Keempat tahap tampil, meski tahap berikutnya belum dimulai
        foreach (['1 · Semai', '2 · Peremajaan', '3 · Pendewasaan', '4 · Panen'] as $tahap) {
            $this->assertStringContainsString($tahap, $html, "Bagian \"{$tahap}\" harus ada");
        }
        $this->assertStringContainsString('Tahap ini belum dimulai', $html);
    }

    /** Batch yang tidak ada harus 404, bukan jatuh ke route resource semai. */
    public function test_detail_batch_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->admin)->get('/semai/batch/99999')->assertNotFound();
    }

    /** 2) Halaman Master Siklus kini punya tombol tarik CSV yang benar-benar berfungsi. */
    public function test_tarik_siklus_csv_mengeluarkan_data_master(): void
    {
        // URL di HTML tampil penuh (http://…/siklus/csv), jadi dicocokkan
        // pola rutenya, bukan nama route Laravel.
        $this->actingAs($this->admin)->get('/siklus')->assertOk()
            ->assertSee('/siklus/csv', false);

        $response = $this->actingAs($this->admin)->get('/siklus/csv');
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('NAMA SIKLUS', $csv);
        $this->assertStringContainsString('DURASI (HARI)', $csv);
        $this->assertStringContainsString('Siklus Uji', $csv);
        $this->assertStringContainsString('Selada', $csv);
    }

    /** 3) Pratinjau sensor dipaginasi: tidak semua baris dijejalkan sekaligus. */
    public function test_tarik_sensor_menampilkan_sebagian_baris_saja(): void
    {
        // 25 baris sensor — sebelumnya semuanya tampil dalam satu layar.
        foreach (range(1, 25) as $i) {
            Monitoring::create([
                'ph' => 6.5, 'tds' => 500, 'waterTemp' => 25.0,
                'airTemp' => 30.0, 'humidity' => 70.0, 'water_level' => 'Sedang',
                'created_at' => now()->subMinutes($i), 'updated_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/monitoring/tarik');
        $response->assertOk();

        $html = $response->getContent();

        // Hanya 10 baris data per halaman (default), padahal total 25.
        // <tbody> dihitung terpisah karena <thead> juga punya satu <tr>.
        $this->assertMatchesRegularExpression('/<tbody>(.*?)<\/tbody>/s', $html);
        preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $m);
        $this->assertSame(
            10,
            substr_count($m[1], '<tr>'),
            'Tabel seharusnya hanya memuat 10 baris per halaman'
        );

        // Paginasi tampil dan totalnya benar.
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringContainsString('25', $html);
    }

    /** CSV sensor TIDAK ikut terpotong paginasi — tetap seluruh rentang. */
    public function test_csv_sensor_tetap_mengeluarkan_seluruh_data(): void
    {
        foreach (range(1, 25) as $i) {
            Monitoring::create([
                'ph' => 6.5, 'tds' => 500, 'waterTemp' => 25.0,
                'airTemp' => 30.0, 'humidity' => 70.0, 'water_level' => 'Sedang',
                'created_at' => now()->subMinutes($i), 'updated_at' => now()->subMinutes($i),
            ]);
        }

        $csv = $this->actingAs($this->admin)
            ->get('/monitoring/tarik/csv')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('SUHU AIR (C)', $csv);
        // 25 baris data + 1 baris header.
        $this->assertSame(26, substr_count($csv, "\n"), 'CSV harus memuat semua baris tanpa paginasi');
    }
}
