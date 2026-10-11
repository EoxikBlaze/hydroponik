@extends('layouts.app')
@section('title','Ambang Batas Sensor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-gauge-high me-2 text-primary"></i>Master Ambang Batas Sensor</h4>
        <p class="text-muted small mb-0">Batas normal tiap sensor — dipakai alat untuk peringatan & dibaca ESP32</p>
    </div>
    @unless(auth()->user()->is_demo)
    <a href="{{ route('ambang.create') }}" class="btn btn-emerald px-3 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm">
        <i class="fas fa-plus"></i>
        <span>Tambah Ambang</span>
    </a>
    @endunless
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3" style="width: 60px;">No</th>
                    <th>Sensor</th>
                    <th>Nilai Minimum</th>
                    <th>Nilai Maksimum</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ambangs as $a)
                <tr>
                    <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                    <td><strong class="text-dark">{{ $a->sensor }}</strong></td>
                    <td><span class="badge bg-light text-dark border px-2 py-1">{{ $a->min }}</span></td>
                    <td><span class="badge bg-light text-dark border px-2 py-1">{{ $a->max }}</span></td>
                    <td class="text-end pe-3">@unless(auth()->user()->is_demo)
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('ambang.edit', $a->id) }}" class="btn btn-sm btn-light border text-warning" title="Edit">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('ambang.destroy', $a->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus ambang sensor ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endunless</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada ambang sensor — alat memakai nilai bawaan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection