@extends('layouts.app')
@section('title', 'Penerima Notifikasi WhatsApp')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-address-book me-2 text-success"></i>Daftar Penerima Notifikasi WhatsApp</h4>
        <p class="text-muted small mb-0">Nomor WhatsApp penanggung jawab yang akan menerima peringatan anomali sensor dan pengingat siklus</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('notifikasi-wa.index') }}" class="btn btn-outline-secondary btn-sm px-3 py-2">
            <i class="fab fa-whatsapp me-1"></i> Gateway WA
        </a>
        <a href="{{ route('penerima_notif.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
            <i class="fas fa-plus me-1"></i> Tambah Kontak Baru
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama Staf / Penerima</th>
                        <th>Nomor WhatsApp</th>
                        <th>Format Internasional</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penerima as $p)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; color: #059669; font-weight: 700;">
                                    {{ strtoupper(substr($p->nama, 0, 1)) }}
                                </div>
                                <span class="fw-bold text-dark">{{ $p->nama }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="font-monospace fw-semibold text-dark">{{ $p->no_hp }}</span>
                        </td>
                        <td>
                            <span class="badge badge-soft-success">
                                <i class="fab fa-whatsapp me-1"></i>{{ $p->clean_number }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('penerima_notif.edit', $p->id_penerima_notif) }}" class="btn btn-light border text-secondary" title="Edit">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('penerima_notif.destroy', $p->id_penerima_notif) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus nomor kontak ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light border text-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fas fa-users-slash fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Penerima Notifikasi</h6>
                            <p class="small text-muted mb-3">Daftarkan nomor WhatsApp staf untuk menerima broadcast otomatis.</p>
                            <a href="{{ route('penerima_notif.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Kontak Baru
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
