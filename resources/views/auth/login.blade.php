@extends('layouts.app')
@section('title', 'Masuk ke Sistem')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, rgba(11, 19, 41, 0.88), rgba(5, 150, 105, 0.85)),
                    url('{{ asset("images/bghidro.jpg") }}') center/cover no-repeat fixed !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 20px;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        max-width: 440px;
        width: 100%;
        margin: auto;
    }

    .login-brand-header {
        text-align: center;
        padding: 2.5rem 2rem 1.5rem;
    }

    .login-logo {
        width: 76px;
        height: 76px;
        object-fit: contain;
        background: #ffffff;
        border-radius: 18px;
        padding: 10px;
        box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.25);
        margin-bottom: 1.25rem;
    }

    .input-group-custom .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        font-size: 0.95rem;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
    }

    .input-group-custom .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }

    .input-group-custom .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        z-index: 10;
        transition: color 0.2s;
    }

    .input-group-custom .form-control:focus + .input-icon,
    .input-group-custom:focus-within .input-icon {
        color: #059669;
    }

    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="login-card">
    <div class="login-brand-header">
        <img src="{{ asset('images/logo.png') }}" alt="HarvestHouse Logo" class="login-logo" onerror="this.src='{{ asset('images/logoh.png') }}'">
        <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">HarvestHouse</h3>
        <p class="text-muted small mb-0">Sistem Cerdas Manajemen & Monitoring Hidroponik</p>
    </div>

    <div class="px-4 pb-4 pt-1">
        @if($errors->any())
            <div class="alert alert-danger border-0 d-flex align-items-center gap-2 small py-2 mb-3 rounded-3" style="background-color: #fef2f2; color: #991b1b;">
                <i class="fas fa-circle-exclamation text-danger"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.proses') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary mb-1">Nama Pengguna / Username</label>
                <div class="position-relative input-group-custom">
                    <input type="text" name="username" class="form-control"
                           value="{{ old('username') }}" placeholder="Masukkan username admin" required autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary mb-1">Kata Sandi / Password</label>
                <div class="position-relative input-group-custom">
                    <input type="password" id="passwordInput" name="password" class="form-control"
                           placeholder="••••••••" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye" id="passwordEye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-emerald w-100 py-2 rounded-3 fs-6 d-flex align-items-center justify-content-center gap-2">
                <span>Masuk ke Dashboard</span>
                <i class="fas fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-2 border-top">
            <small class="text-muted d-block" style="font-size: 0.72rem;">
                © {{ date('Y') }} HarvestHouse • Politeknik Negeri Tanah Laut
            </small>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const input = document.getElementById('passwordInput');
    const eye = document.getElementById('passwordEye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>
@endsection
