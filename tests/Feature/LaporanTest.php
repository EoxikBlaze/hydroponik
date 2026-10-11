<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\Panen;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Tanaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['level' => 'admin']);
    }

    /** Dua batch Selada di meja berbeda (A & B) — uji pemisahan per meja. */
    private function duaBatch(): void
    {
        $mejaA = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $mejaB = Meja::create(['meja' => 'Meja B', 'jumlah_lubang' => 30]);
        $selada = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $mejaA->id_meja]);

        foreach ([[$mejaA->id_meja, 20, 2], [$mejaB->id_meja, 10, 1]] as [$idMeja, $berhasil, $gagal]) {
            $semai = Semai::create([
                'id_tanaman' => $selada->id_tanaman, 'id_meja' => $idMeja, 'jumlah_benih' => 30,
                'tgl_awal_semai' => '2026-06-01', 'tgl_akhir_semai' => '2026-06-06',
                'status' => 'selesai', 'benih_berhasil' => $berhasil, 'benih_gagal' => $gagal,
            ]);
            $per = Peremajaan::create([
                'id_semai' => $semai->id_semai, 'id_meja' => $idMeja,
                'benih_berhasil' => $berhasil - 2, 'benih_gagal' => 1,
                'tgl_awal_peremajaan' => '2026-06-07', 'tgl_akhir_peremajaan' => '2026-06-14',
            ]);
            $pen = Pendewasaan::create([
                'id_peremajaan' => $per->id_peremajaan, 'id_meja' => $idMeja,
                'tanaman_berhasil' => $berhasil - 3, 'tanaman_gagal' => 1,
                'tgl_awal_pendewasaan' => '2026-06-15', 'tgl_akhir_pendewasaan' => '2026-06-30',
            ]);
            Panen::create([
                'id_pendewasaan' => $pen->id_pendewasaan,
                'panen_berhasil' => $berhasil - 4, 'panen_gagal' => 1, 'tgl_panen' => '2026-07-01',
            ]);
        }
    }

    public function test_laporan_memisahkan_per_batch(): void
    {
        $this->duaBatch();

        $resp = $this->actingAs($this->admin)->get('/laporan?periode=semua');
        $resp->assertOk();
        // Kolom sekarang berlabel BATCH (bukan MEJA (BATCH)).
        $resp->assertSee('>BATCH<', false);
        $resp->assertDontSee('MEJA (BATCH)');
        // Tiap batch jadi baris terpisah dengan nomor batch sendiri.
        $resp->assertSee('#1');
        $resp->assertSee('#2');
        $this->assertSame(2, substr_count($resp->getContent(), 'badge badge-soft-info">'));
    }

    /**
     * Inti permintaan: dua batch pada MEJA YANG SAMA harus tetap jadi dua
     * baris terpisah — dulu digabung karena dikelompokkan per meja.
     */
    public function test_dua_batch_di_meja_sama_tetap_terpisah(): void
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $selada = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);

        foreach ([[20, 2], [10, 1]] as [$berhasil, $gagal]) {
            Semai::create([
                'id_tanaman' => $selada->id_tanaman, 'id_meja' => $meja->id_meja, 'jumlah_benih' => 30,
                'tgl_awal_semai' => '2026-06-01', 'tgl_akhir_semai' => '2026-06-06',
                'status' => 'selesai', 'benih_berhasil' => $berhasil, 'benih_gagal' => $gagal,
            ]);
        }

        $resp = $this->actingAs($this->admin)->get('/laporan?periode=semua');
        $resp->assertOk();
        // Dua baris meski meja sama, dan keduanya tetap dijumlah di total.
        $this->assertSame(2, substr_count($resp->getContent(), 'badge badge-soft-info">'));
        $resp->assertSee('<td class="text-success">30</td>', false);
    }

    public function test_laporan_menjumlahkan_dengan_benar(): void
    {
        $this->duaBatch();

        $resp = $this->actingAs($this->admin)->get('/laporan?periode=semua');
        // total semai berhasil = 20 + 10 = 30
        $resp->assertSee('<td class="text-success">30</td>', false);
    }

    public function test_filter_periode_bulan_bekerja(): void
    {
        $this->duaBatch();

        // Juni 2026 -> data semai ada; Januari 2026 -> kosong
        $respJuni = $this->actingAs($this->admin)->get('/laporan?periode=bulan&tahun=2026&bulan=6');
        $respJuni->assertOk();
        $respJuni->assertSee('<td class="text-success">30</td>', false);

        $respJan = $this->actingAs($this->admin)->get('/laporan?periode=bulan&tahun=2026&bulan=1');
        $respJan->assertOk();
        $respJan->assertSee('TOTAL KESELURUHAN');
        $respJan->assertDontSee('<td class="text-success">30</td>', false);
    }

    public function test_ekspor_csv_berisi_data_dan_total(): void
    {
        $this->duaBatch();

        $resp = $this->actingAs($this->admin)->get('/laporan/csv?periode=semua');
        $resp->assertOk();
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));

        $body = $resp->streamedContent();
        $this->assertStringContainsString('TANAMAN', $body);
        $this->assertStringContainsString('BATCH', $body);
        $this->assertStringContainsString('MULAI SEMAI', $body);
        $this->assertStringContainsString('#1', $body);
        $this->assertStringContainsString('Selada', $body);
        $this->assertStringContainsString('TOTAL KESELURUHAN', $body);
    }

    public function test_petani_tidak_bisa_buka_laporan(): void
    {
        $petani = User::factory()->create(['level' => 'petani']);
        $this->actingAs($petani)->get('/laporan')->assertRedirect(route('tanam.index'));
        $this->actingAs($petani)->get('/laporan/csv')->assertRedirect(route('tanam.index'));
    }
}
