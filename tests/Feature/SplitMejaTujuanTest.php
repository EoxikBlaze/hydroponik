<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\Panen;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Split meja tujuan: satu tahap boleh dibelah ke BEBERAPA meja
 * (mis. 400 -> Pendewasaan 1 (200) + Pendewasaan 2 (200)),
 * selama total alokasi = hasil berhasil dan tiap meja muat jatahnya.
 */
class SplitMejaTujuanTest extends TestCase
{
    use RefreshDatabase;

    private User $petani;

    private Tanaman $tanaman;

    private Peremajaan $per;

    private Meja $pendewasaan1;   // 200 lubang

    private Meja $pendewasaan2;   // 200 lubang

    protected function setUp(): void
    {
        parent::setUp();
        $this->petani = User::factory()->create(['level' => 'petani']);

        $sem = Meja::create(['meja' => 'Meja Semai', 'jumlah_lubang' => 500]);
        $this->pendewasaan1 = Meja::create(['meja' => 'Pendewasaan 1', 'jumlah_lubang' => 200]);
        $this->pendewasaan2 = Meja::create(['meja' => 'Pendewasaan 2', 'jumlah_lubang' => 200]);

        $this->tanaman = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $sem->id_meja]);
        foreach ([['Semai', 5], ['Peremajaan', 7], ['Pendewasaan', 8]] as [$n, $h]) {
            Siklus::create(['id_tanaman' => $this->tanaman->id_tanaman, 'nama_siklus' => $n, 'waktu_siklus' => $h]);
        }

        $semai = Semai::create([
            'id_tanaman' => $this->tanaman->id_tanaman, 'id_meja' => $sem->id_meja,
            'jumlah_benih' => 500, 'benih_berhasil' => 400, 'benih_gagal' => 100,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'selesai',
        ]);
        $this->per = Peremajaan::create([
            'id_semai' => $semai->id_semai, 'id_meja' => $sem->id_meja,
            'benih_berhasil' => 0, 'benih_gagal' => 0,
            'tgl_awal_peremajaan' => '2026-10-06', 'tgl_akhir_peremajaan' => '2026-10-13',
        ]);
    }

    /** @return array<int, array{id_meja: int, jumlah: int}> */
    private function tujuan(array $baris): array
    {
        return $baris;
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'berhasil' => 400,
            'gagal' => 0,
            'tgl_selesai' => '2026-10-13',
            'tujuan' => $this->tujuan([
                ['id_meja' => $this->pendewasaan1->id_meja, 'jumlah' => 200],
                ['id_meja' => $this->pendewasaan2->id_meja, 'jumlah' => 200],
            ]),
        ], $over);
    }

    private function kirim(array $payload)
    {
        return $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$this->per->id_peremajaan}/selesai", $payload);
    }

    /** Kasus inti: 400 ke dua meja berlubang 200 -> BOLEH, jadi dua baris. */
    public function test_split_400_ke_dua_meja_200_dibolehkan(): void
    {
        $this->kirim($this->payload())->assertRedirect(route('tanam.index'));

        $this->assertSame(2, Pendewasaan::count(), 'Hasil split harus jadi dua baris pendewasaan');

        $dibuat = Pendewasaan::orderBy('id_pendewasaan')->get();
        $this->assertSame([$this->pendewasaan1->id_meja, $this->pendewasaan2->id_meja],
            $dibuat->pluck('id_meja')->all());
        $this->assertSame([200, 200], $dibuat->pluck('jumlah_masuk')->all(),
            'Tiap baris harus menyimpan jatahnya sendiri');
    }

    /** Total alokasi harus persis = hasil berhasil (tidak boleh kurang/lebih). */
    public function test_total_alokasi_harus_sama_dengan_berhasil(): void
    {
        $this->kirim($this->payload(['tujuan' => $this->tujuan([
            ['id_meja' => $this->pendewasaan1->id_meja, 'jumlah' => 200],
            ['id_meja' => $this->pendewasaan2->id_meja, 'jumlah' => 150],   // 350 != 400
        ])]))->assertSessionHasErrors('tujuan');

        $this->assertSame(0, Pendewasaan::count(), 'Alokasi tidak cocok tidak boleh menulis apa pun');
    }

    /** Satu meja tetap tidak boleh dilampaui, meski total gabungan muat. */
    public function test_meja_tunggal_tetap_dibatasi_lubangnya(): void
    {
        // Alokasi konsisten (400 = 400) tapi satu meja hanya 200 lubang -> tolak.
        $this->kirim($this->payload([
            'tujuan' => $this->tujuan([['id_meja' => $this->pendewasaan1->id_meja, 'jumlah' => 400]]),
        ]))->assertSessionHasErrors('id_meja');

        $this->assertSame(0, Pendewasaan::count());
    }

    /** Total gagal melebihi batas tetap ditolak SEBELUM validasi alokasi. */
    public function test_total_melebihi_batas_tetap_ditolak(): void
    {
        $this->kirim($this->payload(['berhasil' => 450, 'gagal' => 10]))  // 460 > 400
            ->assertSessionHasErrors('gagal');

        $this->assertSame(0, Pendewasaan::count());
    }

    /** Kirim ganda / dobel-klik: guard tetap menahan, tidak jadi 4 baris. */
    public function test_submit_ganda_tidak_menduplikasi_baris(): void
    {
        $p = $this->payload();
        $this->kirim($p);
        $this->kirim($p);

        $this->assertSame(2, Pendewasaan::count(), 'Submit kedua harus ditangkap guard');
    }

    /** Batas panen tiap baris = jumlah_masuk BUKAN induk (cegah over-record). */
    public function test_batas_pakai_jumlah_masuk_bukan_induk(): void
    {
        $this->kirim($this->payload());

        $baris = Pendewasaan::orderBy('id_pendewasaan')->get();
        foreach ($baris as $b) {
            $this->assertSame(200, (int) $b->jumlah_masuk);
            // Sebelumnya batas diambil dari peremajaan (400) -> kelebihan 2x.
            $this->assertNotSame(400, (int) $b->jumlah_masuk);
        }
    }

    /** Baris LAMA (jumlah_masuk NULL) tetap jalan — fallback ke batas induk. */
    public function test_baris_lama_tanpa_jumlah_masuk_fallback_induk(): void
    {
        // Peremajaan sudah ditandai selesai (400 hasil) lalu pendewasaan dibuat
        // dengan cara LAMA -> jumlah_masuk bernilai NULL seperti data lama.
        $this->per->update(['benih_berhasil' => 400, 'benih_gagal' => 100]);
        Pendewasaan::create([
            'id_peremajaan' => $this->per->id_peremajaan,
            'id_meja' => $this->pendewasaan1->id_meja,
            'tanaman_berhasil' => 0, 'tanaman_gagal' => 0,
            'tgl_awal_pendewasaan' => '2026-10-13', 'tgl_akhir_pendewasaan' => '2026-10-21',
        ]);

        $baris = Pendewasaan::firstOrFail();
        $this->assertNull($baris->jumlah_masuk);

        // Konteks harus fallback ke benih_berhasil induk (400), bukan 0.
        $this->actingAs($this->petani)
            ->post("/tanam/pendewasaan/{$baris->id_pendewasaan}/selesai", [
                'berhasil' => 400, 'gagal' => 0,
                'panen_berhasil' => 400, 'panen_gagal' => 0,
                'tgl_selesai' => '2026-10-21',
            ])->assertRedirect(route('tanam.index'));

        $this->assertSame(1, Panen::count());
    }
}
