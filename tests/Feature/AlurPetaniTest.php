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

class AlurPetaniTest extends TestCase
{
    use RefreshDatabase;

    private User $petani;

    protected function setUp(): void
    {
        parent::setUp();
        $this->petani = User::factory()->create(['level' => 'petani']);
    }

    /**
     * Master data: Meja A (50 lubang), Meja B (30 lubang), tanaman Selada,
     * durasi siklus (Semai 5, Peremajaan 7, Pendewasaan 8 hari).
     */
    private function masterData(): Tanaman
    {
        $meja = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        Meja::create(['meja' => 'Meja B', 'jumlah_lubang' => 30]);
        $tanaman = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $meja->id_meja]);
        foreach ([['Semai', 5], ['Peremajaan', 7], ['Pendewasaan', 8]] as [$nama, $hari]) {
            Siklus::create(['id_tanaman' => $tanaman->id_tanaman, 'nama_siklus' => $nama, 'waktu_siklus' => $hari]);
        }

        return $tanaman;
    }

    public function test_login_petani_langsung_ke_halaman_tanam(): void
    {
        $this->post('/login', ['username' => $this->petani->username, 'password' => 'password'])
            ->assertRedirect('/tanam');
    }

    public function test_mulai_tanam_otomatis_buat_semai_dengan_durasi_master(): void
    {
        $tanaman = $this->masterData();

        $this->actingAs($this->petani)->post('/tanam/mulai', [
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1,
            'jumlah_benih' => 40, 'tgl_mulai' => '2026-10-01',
        ])->assertRedirect(route('tanam.index'));

        $semai = Semai::first();
        $this->assertNotNull($semai);
        $this->assertSame('proses', $semai->status);
        $this->assertSame(1, $semai->id_meja, 'Meja semai dari pilihan petani');
        // durasi Semai dari master = 5 hari
        $this->assertSame('2026-10-06', $semai->tgl_akhir_semai);
    }

    public function test_selesai_semai_otomatis_buat_peremajaan(): void
    {
        $tanaman = $this->masterData();
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1, 'jumlah_benih' => 40,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);

        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", [
            'id_meja' => 2, 'berhasil' => 25, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
        ])->assertRedirect(route('tanam.index'));

        $semai->refresh();
        $this->assertSame('selesai', $semai->status);
        $this->assertSame(25, $semai->benih_berhasil);

        $per = Peremajaan::first();
        $this->assertNotNull($per, 'Peremajaan harus dibuat otomatis');
        $this->assertSame($semai->id_semai, $per->id_semai);
        $this->assertSame(2, $per->id_meja, 'Peremajaan pindah ke meja pilihan petani (Meja B)');
        // durasi Peremajaan dari master = 7 hari
        $this->assertSame('2026-10-13', $per->tgl_akhir_peremajaan);
    }

    public function test_siklus_penuh_sampai_panen_semua_otomatis(): void
    {
        $tanaman = $this->masterData();
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1, 'jumlah_benih' => 40,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);

        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", [
            'id_meja' => 1, 'berhasil' => 30, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
        ]);

        $per = Peremajaan::first();
        $this->actingAs($this->petani)->post("/tanam/peremajaan/{$per->id_peremajaan}/selesai", [
            'id_meja' => 2, 'berhasil' => 25, 'gagal' => 3, 'tgl_selesai' => '2026-10-13',
        ]);

        $pen = Pendewasaan::first();
        $this->assertNotNull($pen, 'Pendewasaan harus dibuat otomatis');
        $this->assertSame(2, $pen->id_meja, 'Pendewasaan pindah ke meja pilihan petani');
        $this->assertSame('2026-10-21', $pen->tgl_akhir_pendewasaan, 'Durasi Pendewasaan dari master = 8 hari');

        $this->actingAs($this->petani)->post("/tanam/pendewasaan/{$pen->id_pendewasaan}/selesai", [
            'berhasil' => 22, 'gagal' => 2,
            'panen_berhasil' => 20, 'panen_gagal' => 2,
            'tgl_selesai' => '2026-10-21',
        ]);

        $panen = Panen::first();
        $this->assertNotNull($panen, 'Panen harus tercatat otomatis');
        $this->assertSame(20, $panen->panen_berhasil);
        $this->assertSame('2026-10-21', $panen->tgl_panen->toDateString());
    }

    public function test_batch_selada_dibedakan_per_meja(): void
    {
        $tanaman = $this->masterData();   // Meja A (50 lubang), Meja B (30 lubang)

        foreach ([1, 2] as $idMeja) {
            $this->actingAs($this->petani)->post('/tanam/mulai', [
                'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => $idMeja,
                'jumlah_benih' => 10, 'tgl_mulai' => '2026-10-01',
            ]);
        }

        $this->assertSame([1, 2], Semai::orderBy('id_semai')->pluck('id_meja')->all(),
            'Dua batch Selada harus punya meja berbeda');
    }

    public function test_jumlah_lubang_meja_jadi_batas(): void
    {
        $tanaman = $this->masterData();   // Meja B = 30 lubang

        // 40 benih > 30 lubang Meja B -> tolak
        $this->actingAs($this->petani)->post('/tanam/mulai', [
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 2,
            'jumlah_benih' => 40, 'tgl_mulai' => '2026-10-01',
        ])->assertSessionHasErrors('jumlah_benih');

        // pindah fase ke meja yang lubangnya kurang -> tolak
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1, 'jumlah_benih' => 50,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", [
            'id_meja' => 2, 'berhasil' => 35, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
        ])->assertSessionHasErrors('id_meja');   // 35 > 30 lubang Meja B

        $this->assertNull(Peremajaan::first());
    }

    public function test_petani_tidak_bisa_buka_master_data(): void
    {
        foreach (['/meja', '/tanaman', '/siklus', '/user', '/laporan'] as $uri) {
            $this->actingAs($this->petani)->get($uri)->assertRedirect(route('tanam.index'));
        }
    }

    public function test_submit_ganda_tidak_menduplikasi_tahap(): void
    {
        $tanaman = $this->masterData();
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1, 'jumlah_benih' => 40,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);

        $payload = ['id_meja' => 1, 'berhasil' => 35, 'gagal' => 5, 'tgl_selesai' => '2026-10-06'];
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", $payload);
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", $payload);
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", $payload);

        $this->assertSame(1, Peremajaan::count(), 'Double-submit tidak boleh membuat peremajaan ganda');
    }

    public function test_tipe_ngawur_dan_id_hantu_ditolak(): void
    {
        $this->actingAs($this->petani)->post('/tanam/XX/1/selesai', ['berhasil' => 1, 'gagal' => 0, 'tgl_selesai' => '2026-10-06'])
            ->assertStatus(404);
        $this->actingAs($this->petani)->post('/tanam/panen/1/selesai', ['berhasil' => 1, 'gagal' => 0, 'tgl_selesai' => '2026-10-06'])
            ->assertStatus(404);
        $this->actingAs($this->petani)->post('/tanam/semai/99999/selesai', ['berhasil' => 1, 'gagal' => 0, 'tgl_selesai' => '2026-10-06'])
            ->assertStatus(404);
    }

    public function test_petani_post_master_data_ditolak(): void
    {
        $this->actingAs($this->petani)->post('/tanaman', ['nama_tanaman' => 'HACK'])
            ->assertRedirect(route('tanam.index'));
        $this->assertDatabaseCount('tanamen', 0);
    }

    public function test_batasan_jumlah_tetap_dijaga(): void
    {
        $tanaman = $this->masterData();
        $semai = Semai::create([
            'id_tanaman' => $tanaman->id_tanaman, 'id_meja' => 1, 'jumlah_benih' => 40,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);

        // 90 + 20 = 110 > 100 benih -> tolak
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", [
            'id_meja' => 1, 'berhasil' => 30, 'gagal' => 25, 'tgl_selesai' => '2026-10-06',
        ])->assertSessionHasErrors('gagal');

        // panen melebihi tanaman berhasil -> tolak
        $this->actingAs($this->petani)->post("/tanam/semai/{$semai->id_semai}/selesai", [
            'id_meja' => 1, 'berhasil' => 30, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
        ]);
        $per = Peremajaan::first();
        $this->actingAs($this->petani)->post("/tanam/peremajaan/{$per->id_peremajaan}/selesai", [
            'id_meja' => 1, 'berhasil' => 25, 'gagal' => 3, 'tgl_selesai' => '2026-10-13',
        ]);
        $pen = Pendewasaan::first();
        $this->actingAs($this->petani)->post("/tanam/pendewasaan/{$pen->id_pendewasaan}/selesai", [
            'berhasil' => 22, 'gagal' => 2,
            'panen_berhasil' => 30, 'panen_gagal' => 0,   // 30 > 22 tanaman berhasil
            'tgl_selesai' => '2026-10-21',
        ])->assertSessionHasErrors('panen_gagal');

        $this->assertNull(Panen::first());
    }
}
