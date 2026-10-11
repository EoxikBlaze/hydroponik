@extends('layouts.app')
@section('title','Notifikasi WhatsApp')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fab fa-whatsapp me-2 text-success"></i>Notifikasi WhatsApp (Fonnte)</h4>
        <p class="text-muted small mb-0">Atur API key Fonnte & nomor tujuan — peringatan kebun dikirim langsung ke WhatsApp Anda</p>
    </div>
    @if($set->aktif && $set->api_key && count($set->daftarTarget()) > 0)
        <span class="badge bg-success px-3 py-2 rounded-3"><i class="fas fa-circle-check me-1"></i>AKTIF</span>
    @else
        <span class="badge bg-secondary px-3 py-2 rounded-3"><i class="fas fa-circle-minus me-1"></i>NONAKTIF</span>
    @endif
</div>

@if(auth()->user()->is_demo)
<div class="alert alert-info">
    <i class="fas fa-circle-info me-1"></i>
    Akun demo hanya dapat melihat pengaturan — perubahan API key dinonaktifkan.
</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fab fa-whatsapp fs-4"></i>
                    <div>
                        <h5 class="mb-0">⚙️ Pengaturan Fonnte</h5>
                        <small class="opacity-75">API key tersimpan aman di database — tidak di file .env</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('notifikasi-wa.update') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" {{ old('aktif', $set->aktif) ? 'checked' : '' }} {{ auth()->user()->is_demo ? 'disabled' : '' }}>
                                <label class="form-check-label fw-bold" for="aktif">Aktifkan notifikasi WhatsApp</label>
                            </div>
                            <small class="text-muted">Matikan kapan saja tanpa menghapus API key.</small>
                        </div>

                        <div class="col-12 d-flex align-items-center gap-2 mt-2">
                            <i class="fas fa-key text-primary small"></i>
                            <span class="fw-bold small text-uppercase text-muted">Kredensial Fonnte</span>
                            <hr class="flex-grow-1 ms-2 my-0">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small">API Key Fonnte (Token)</label>
                            <input type="password" name="api_key" class="form-control @error('api_key') is-invalid @enderror"
                                   value="{{ old('api_key') }}" autocomplete="off"
                                   placeholder="{{ $set->api_key ? '•••••••• — kosongkan bila tidak ingin mengganti' : 'Tempel token dari dashboard Fonnte' }}"
                                   {{ auth()->user()->is_demo ? 'disabled' : '' }}>
                            @error('api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">
                                @if($set->api_key)
                                    Token tersimpan (akhir: <strong>••••{{ substr($set->api_key, -4) }}</strong>) — isi hanya bila ingin mengganti.
                                @else
                                    Belum ada token tersimpan.
                                @endif
                            </small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small">Nomor Tujuan</label>
                            <input type="text" name="target" class="form-control @error('target') is-invalid @enderror"
                                   value="{{ old('target', $set->target) }}"
                                   placeholder="081234567890, 089876543210"
                                   {{ auth()->user()->is_demo ? 'disabled' : '' }}>
                            @error('target')<div class="invalid-feedback">{{ $message }}</div>@enderror>
                            <small class="text-muted">Bisa lebih dari satu nomor — pisahkan dengan tanda koma. Format 08xx atau 628xx.</small>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted"><i class="fas fa-shield-halved me-1"></i>Peringatan yang dikirim: solenoid ON/OFF (auto & manual), level air Penuh/Kosong, jadwal H-1 pindah meja, pindah tanam (ganti tahap), dan ringkasan rata-rata sensor tiap pagi jam07:00 WITA.</small>
                        @unless(auth()->user()->is_demo)
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Simpan</button>
                        @endunless
                    </div>
                </form>

                @unless(auth()->user()->is_demo)
                <form action="{{ route('notifikasi-wa.uji') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-success w-100" {{ ($set->aktif && $set->api_key && count($set->daftarTarget()) > 0) ? '' : 'disabled' }}>
                        <i class="fas fa-paper-plane me-1"></i>Kirim Pesan Uji
                    </button>
                    <small class="text-muted d-block text-center mt-1">Pastikan sudah Simpan dulu, lalu tekan tombol ini untuk membuktikan pesan sampai.</small>
                </form>
                @endunless
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-light py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-circle-question text-primary fs-5"></i>
                    <h5 class="mb-0 fw-bold text-dark">Cara Mendapatkan API Key Fonnte</h5>
                </div>
            </div>
            <div class="card-body p-4">
                <ol class="mb-0 small">
                    <li class="mb-2">Daftar / login di <strong>fonnte.com</strong>.</li>
                    <li class="mb-2">Pasang aplikasi Fonnte di HP (jadikan <em>device</em> pengirim), lalu sambungkan.</li>
                    <li class="mb-2">Di dashboard Fonnte, salin <strong>Token</strong> — itulah API key-nya.</li>
                    <li class="mb-2">Tempel token ke kolom <strong>API Key Fonnte</strong> di samping, isi nomor tujuan, centang <strong>Aktifkan</strong>, lalu <strong>Simpan</strong>.</li>
                    <li>Tekan <strong>Kirim Pesan Uji</strong> — bila pesan masuk ke WhatsApp, notifikasi sudah aktif.</li>
                </ol>
                <div class="alert alert-light border small mb-0 mt-3">
                    <i class="fas fa-lightbulb text-warning me-1"></i>
                    Status device harus <strong>tersambung</strong> di dashboard Fonnte. Bila pesan uji gagal, periksa dulu sambungan device di sana.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
