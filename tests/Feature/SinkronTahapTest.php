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
 * Dua bug yang pernah terjadi saat satu tahap di-split ke beberapa meja:
 *
 * 1) KARTU SALAH BACA TAHAP — TanamController::index() dulu mengambil
 *    peremajaan PERTAMA lalu turun ke anaknya. Bila baris pertama belum punya
 *    anak padahal baris lain sudah, kartu tetap menampilkan "Tandai Peremajaan
 *    Selesai" meski data sudah di Pendewasaan.
 *
 * 2) DATA TAHAP TAMPIL BANYAK BARIS — satu batch split jadi N baris, sehingga
 *    terbaca sebagai N batch. Sekarang digabung jadi 1 baris per batch.
 */
class SinkronTahapTest extends TestCase
{
    use RefreshDatabase;

    private User $petani;

    private User $admin;

    private Tanaman $tanaman;

    private Meja $mejaA;

    private Meja $mejaB;

    private Semai $semai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->petani = User::factory()->create(['level' => 'petani']);
        // Halaman Data Tahap dibatasi untuk petani (PetaniGuard) -> pakai admin.
        $this->admin = User::factory()->create(['level' => 'admin']);

        $this->mejaA = Meja::create(['meja' => 'Meja A', 'jumlah_lubang' => 50]);
        $this->mejaB = Meja::create(['meja' => 'Meja B', 'jumlah_lubang' => 50]);
        $this->tanaman = Tanaman::create(['nama_tanaman' => 'Selada', 'id_meja' => $this->mejaA->id_meja]);

        foreach ([['Semai', 5], ['Peremajaan', 7], ['Pendewasaan', 8]] as [$n, $h]) {
            Siklus::create(['id_tanaman' => $this->tanaman->id_tanaman, 'nama_siklus' => $n, 'waktu_siklus' => $h]);
        }

        $this->semai = Semai::create([
            'id_tanaman' => $this->tanaman->id_tanaman, 'id_meja' => $this->mejaA->id_meja,
            'jumlah_benih' => 40, 'benih_berhasil' => 0, 'benih_gagal' => 0,
            'tgl_awal_semai' => '2026-10-01', 'tgl_akhir_semai' => '2026-10-06', 'status' => 'proses',
        ]);
    }

    /** Split semai -> 2 baris peremajaan (20 + 20). */
    private function splitSemai(): void
    {
        $this->actingAs($this->petani)
            ->post("/tanam/semai/{$this->semai->id_semai}/selesai", [
                'berhasil' => 40, 'gagal' => 0, 'tgl_selesai' => '2026-10-06',
                'tujuan' => [
                    ['id_meja' => $this->mejaA->id_meja, 'jumlah' => 20],
                    ['id_meja' => $this->mejaB->id_meja, 'jumlah' => 20],
                ],
            ])->assertRedirect(route('tanam.index'));
    }

    /**
     * BUG 1: yang punya anak adalah baris KEDUA, bukan pertama.
     * Kartu wajib membaca Pendewasaan — bukan Peremajaan.
     */
    public function test_kartu_membaca_tahap_dari_seluruh_baris_bukan_baris_pertama(): void
    {
        $this->splitSemai();
        $this->assertSame(2, Peremajaan::where('id_semai', $this->semai->id_semai)->count());

        // Tandai peremajaan KEDUA (baris pertama sengaja dibiarkan tanpa anak).
        $kedua = Peremajaan::where('id_semai', $this->semai->id_semai)
            ->orderByDesc('id_peremajaan')->firstOrFail();

        $this->actingAs($this->petani)
            ->post("/tanam/peremajaan/{$kedua->id_peremajaan}/selesai", [
                'berhasil' => 20, 'gagal' => 0, 'tgl_selesai' => '2026-10-13',
                'tujuan' => [['id_meja' => $this->mejaA->id_meja, 'jumlah' => 20]],
            ])->assertRedirect(route('tanam.index'));

        $this->assertSame(1, Pendewasaan::count());

        // Baris PERTAMA sengaja tanpa anak — itulah pemicu bug lamanya.
        $pertama = Peremajaan::where('id_semai', $this->semai->id_semai)
            ->orderBy('id_peremajaan')->firstOrFail();
        $this->assertFalse($pertama->pendewasaan()->exists(), 'baris pertama harus tanpa anak');

        $html = $this->actingAs($this->petani)->get('/tanam')->assertOk()->getContent();
        $blok = substr($html, strpos($html, "Batch #{$this->semai->id_semai}"), 1600);

        // BUG ASLI: kartu wajib TAHU bahwa batch ini sudah punya Pendewasaan,
        // padahal baris pertama (yang menjadi baris teratas) masih kosong
        // anaknya. Kalau kartu kembali membaca cuma baris pertama, tombol
        // "Catat Hasil Panen" ini lenyap dan test gagal.
        $this->assertStringContainsString('data-tipe="pendewasaan"', $blok);
        $this->assertStringContainsString('Catat Hasil Panen', $blok);

        // PERILAKU YANG DIPILIH (per baris): baris pertama yang belum punya
        // anak TETAP harus punya tombolnya sendiri, lengkap dengan batas
        // baris itu (jumlah_masuk), supaya tidak tersangkung selamanya.
        $this->assertStringContainsString('data-tipe="peremajaan"', $blok);
        $this->assertMatchesRegularExpression(
            '/data-tipe="peremajaan"\s+data-id="'.$pertama->id_peremajaan.'"/',
            $blok,
            'Baris peremajaan yang belum ditandai wajib punya tombol sendiri'
        );
        $this->assertMatchesRegularExpression(
            '/data-tipe="peremajaan".*?data-batas="'.$pertama->jumlah_masuk.'"/s',
            $blok,
            'Batas tombol harus jumlah_masuk baris itu, bukan hasil semai'
        );

        // Dan tombolnya hanya untuk baris yang BENAR-BENAR belum punya anak —
        // baris kedua (sudah jadi pendewasaan) tidak boleh dapat tombol
        // peremajaan lagi.
        $idKedua = $pertama->id_peremajaan === $kedua->id_peremajaan
            ? null
            : $kedua->id_peremajaan;
        if ($idKedua !== null) {
            $this->assertDoesNotMatchRegularExpression(
                '/data-tipe="peremajaan"\s+data-id="'.$idKedua.'"/',
                $blok,
                'Baris yang sudah punya anak tidak boleh ditandai peremajaan lagi'
            );
        }
    }

    /** BUG 2: split tidak boleh membuat batch baru, dan Data Tahap wajib 1 baris per batch. */
    public function test_split_tidak_membuat_batch_baru_dan_data_tahap_satu_baris(): void
    {
        $sebelum = Semai::count();
        $this->splitSemai();

        // Batch tidak bertambah — hanya baris tahapnya.
        $this->assertSame($sebelum, Semai::count(), 'Split tidak boleh membuat batch baru');
        $this->assertSame(1, Semai::where('id_semai', $this->semai->id_semai)->count());
        $this->assertSame(
            2,
            Peremajaan::where('id_semai', $this->semai->id_semai)->count(),
            'Split seharusnya membuat 2 baris peremajaan (2 meja)'
        );

        $html = $this->actingAs($this->admin)
            ->get('/semai?tahap=peremajaan&per_page=100')->assertOk()->getContent();

        // 1 baris tabel mewakili 1 batch, walau barisnya 2.
        preg_match_all('/Batch <strong>#(\d+)<\/strong>/', $html, $m);
        $daftar = $m[1];
        $this->assertSame(
            count($daftar),
            count(array_unique($daftar)),
            'Data Tahap menampilkan batch ganda: '.implode(',', $daftar)
        );
        $this->assertContains((string) $this->semai->id_semai, $daftar);
        $this->assertSame(1, count(array_keys($daftar, (string) $this->semai->id_semai)),
            'Batch split harus tampil tepat SATU baris');
        // Dan jumlahnya ditandai sebagai split.
        $this->assertStringContainsString('2 meja', $html);
    }

    /**
     * Guard anti-double-submit dulu memakai kolom status, sehingga semai
     * ber-status 'selesai' tanpa anak peremajaan TERKUNCI (tombol ada tapi
     * selalu ditolak). Sekarang patokannya keberadaan anak.
     */
    public function test_status_selesai_tanpa_anak_tidak_mengunci_kartu(): void
    {
        // Semai ditandai selesai lewat form Edit tanpa membuat peremajaan.
        $this->semai->update(['status' => 'selesai', 'benih_berhasil' => 35, 'benih_gagal' => 5]);
        $this->assertSame(0, Peremajaan::where('id_semai', $this->semai->id_semai)->count());

        // Guard tidak boleh menolak hanya karena status.
        $this->actingAs($this->petani)
            ->post("/tanam/semai/{$this->semai->id_semai}/selesai", [
                'berhasil' => 35, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
                'tujuan' => [['id_meja' => $this->mejaA->id_meja, 'jumlah' => 35]],
            ])->assertRedirect(route('tanam.index'));

        $this->assertSame(
            1,
            Peremajaan::where('id_semai', $this->semai->id_semai)->count(),
            'Anak peremajaan seharusnya terbentuk, bukan ditolak guard'
        );

        // Kirim ulang -> guard berbasis anak tetap menahan duplikat.
        $this->actingAs($this->petani)
            ->post("/tanam/semai/{$this->semai->id_semai}/selesai", [
                'berhasil' => 35, 'gagal' => 5, 'tgl_selesai' => '2026-10-06',
                'tujuan' => [['id_meja' => $this->mejaA->id_meja, 'jumlah' => 35]],
            ]);
        $this->assertSame(
            1,
            Peremajaan::where('id_semai', $this->semai->id_semai)->count(),
            'Submit ganda tetap harus ditahan'
        );
    }
}
