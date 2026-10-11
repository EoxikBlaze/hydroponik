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

/**
 * Reproduksi laporan: transisi peremajaan -> pendewasaan.
 * 1) jumlah pindah melebihi lubang meja tujuan -> harus error & TIDAK menulis apa pun.
 * 2) saat error, data malah bertambah (duplikat) -> harus TIDAK terjadi.
 */
class TransisiPendewasaanTest extends TestCase
{
    use RefreshDatabase;

    private User $petani;

    private Tanaman $tanaman;

    private Peremajaan $per;

    protected function setUp(): void
    {
        parent::setUp();
        $this->petani = User::factory()->create(['level' => 'petani']);
        // Meja A 50 lubang, Meja B 20 lubang
        $a = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $b = Meja::create(['meja' => 'Meja B', 'jumlah_lubang' => 20]);
        $this->tanaman = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $a->id_meja]);
        foreach ([['Semai', 5], ['Peremajaan', 7], ['Pendewasaan', 8]] as [$n, $h]) {
            Siklus::create(['id_tanaman' => $this->tanaman->id_tanaman, 'nama_siklus' => $n, 'waktu_siklus' => $h]);
        }
        // Semai sudah selesai, peremajaan berjalan dgn 35 benih berhasil (batas = 35)
        $semai = Semai::create([
            'id_tanaman' => $this->tanaman->id_tanaman, 'id_meja' => $a->id_meja,
            'jumlah_benih' => 40, 'benih_berhasil' => 35, 'benih_gagal' => 5,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'selesai',
        ]);
        $this->per = Peremajaan::create([
            'id_semai' => $semai->id_semai, 'id_meja' => $a->id_meja,
            'benih_berhasil' => 0, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-10-06', 'tgl_akhir_peremajaan' => '2026-10-13',
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'id_meja' => 2,          // Meja B = 20 lubang
            'berhasil' => 30,         // 30 > 20 lubang -> harus tolak
            'gagal' => 3,
            'tgl_selesai' => '2026-10-13',
        ], $over);
    }

    public function test_pindah_melebihi_lubang_tujuan_ditolak(): void
    {
        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $this->payload())
            ->assertSessionHasErrors('id_meja');

        $this->assertSame(0, Pendewasaan::count(), 'Tidak boleh ada Pendewasaan saat kapasitas ditolak');
    }

    public function test_error_kapasitas_tidak_mengubah_record_induk(): void
    {
        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $this->payload());

        $this->per->refresh();
        $this->assertSame(0, (int) $this->per->benih_berhasil, 'Peremajaan tidak boleh di-update saat ditolak');
        $this->assertSame('2026-10-13', $this->per->tgl_akhir_peremajaan, 'Tgl akhir tidak berubah saat ditolak');
    }

    public function test_ulang_kirim_setelah_error_tidak_menduplikasi(): void
    {
        // kirim 3x data yang SAMA (kapasitas ditolak) -> tetap nol
        foreach (range(1, 3) as $i) {
            $this->actingAs($this->petani)
                ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $this->payload());
        }
        $this->assertSame(0, Pendewasaan::count(), 'Gagal kapasitas berulang tidak boleh membuat data');
    }

    public function test_setelah_diperbaiki_valid_baru_satu_data(): void
    {
        // gagal dulu (30 > 20), lalu benarkan (15 <= 20)
        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $this->payload())
            ->assertSessionHasErrors('id_meja');
        $this->assertSame(0, Pendewasaan::count());

        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $this->payload(['berhasil' => 15, 'gagal' => 5]))
            ->assertRedirect(route('tanam.index'));
        $this->assertSame(1, Pendewasaan::count(), 'Data valid harus tepat satu');
    }

    public function test_submit_ganda_pendewasaan_tidak_menduplikasi(): void
    {
        $ok = $this->payload(['berhasil' => 15, 'gagal' => 5]);
        $this->actingAs($this->petani)->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $ok);
        $this->actingAs($this->petani)->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $ok);
        $this->assertSame(1, Pendewasaan::count(), 'Submit ganda harus ditangkap guard');
    }

    public function test_total_lebih_batas_juga_ditolak_tanpa_dupe(): void
    {
        // berhasil 40 + gagal 10 = 50 > batas 35 -> tolak di validasi total
        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai",
                $this->payload(['berhasil' => 40, 'gagal' => 10, 'id_meja' => 1]))
            ->assertSessionHasErrors('gagal');
        $this->assertSame(0, Pendewasaan::count());
    }
}
