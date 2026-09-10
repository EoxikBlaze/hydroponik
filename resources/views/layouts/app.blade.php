<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - HarvestHouse Smart Hydroponic</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary-emerald: #059669;
            --primary-emerald-hover: #047857;
            --primary-glow: rgba(16, 185, 129, 0.25);
            --sidebar-bg: #0b1329;
            --sidebar-hover: #1e293b;
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--body-bg);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            padding: 1.25rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            text-decoration: none;
        }

        .sidebar-brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            border-radius: 10px;
            background: #ffffff; padding: 2px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            padding: 4px;
        }

        .sidebar-brand-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.3px;
            margin: 0;
            line-height: 1.2;
        }

        .sidebar-brand-subtitle {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #64748b;
            padding: 1.25rem 1.25rem 0.5rem;
            display: block;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.65rem 1rem;
            margin: 2px 0.75rem;
            border-radius: 10px;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link i {
            width: 20px;
            font-size: 1rem;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(3px);
        }

        .sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            font-weight: 600;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* TOPBAR */
        .topbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 0 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .topbar-badge-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .topbar-badge-offline {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseDot 1.8s infinite;
        }

        .offline-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
        }

        @keyframes pulseDot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* CONTENT CONTAINER */
        .content-body {
            padding: 1.75rem;
            flex: 1;
        }

        /* CARDS */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-elevated {
            transition: all 0.2s ease;
        }
        .card-elevated:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.07), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.15rem 1.35rem;
            font-weight: 600;
        }

        /* TABLES */
        .table thead th {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            padding: 0.85rem 1rem;
        }

        .table tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            font-size: 0.88rem;
            border-bottom: 1px solid #f1f5f9;
        }

        /* BUTTONS */
        .btn-emerald {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 6px var(--primary-glow);
            transition: all 0.2s ease;
        }
        .btn-emerald:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--primary-glow);
            transform: translateY(-1px);
        }

        /* BADGES */
        .badge-soft-success { background: #dcfce7; color: #15803d; font-weight: 600; }
        .badge-soft-warning { background: #fef3c7; color: #b45309; font-weight: 600; }
        .badge-soft-danger  { background: #fee2e2; color: #b91c1c; font-weight: 600; }
        .badge-soft-info    { background: #e0f2fe; color: #0369a1; font-weight: 600; }
        .badge-soft-primary { background: #e0e7ff; color: #4338ca; font-weight: 600; }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @auth
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <img src="{{ asset('images/logoh.png') }}" alt="HarvestHouse" onerror="this.src='{{ asset('favicon.ico') }}'">
            <div>
                <h1 class="sidebar-brand-title">HarvestHouse</h1>
                <span class="sidebar-brand-subtitle">Smart Hydroponic</span>
            </div>
        </a>

        <div class="py-2">
            <span class="sidebar-section-title">Monitoring & IoT</span>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('monitoring.index') }}" class="nav-link {{ request()->is('monitoring*') ? 'active' : '' }}">
                <i class="fas fa-satellite-dish"></i>
                <span>Sensor Real-Time</span>
            </a>
            <a href="{{ route('watercontrol.index') }}" class="nav-link {{ request()->is('watercontrol*') ? 'active' : '' }}">
                <i class="fas fa-faucet-drip"></i>
                <span>Water Control</span>
            </a>

            <span class="sidebar-section-title">Siklus Hidroponik</span>
            <a href="{{ route('semai.index') }}" class="nav-link {{ request()->is('semai*') ? 'active' : '' }}">
                <i class="fas fa-seedling"></i>
                <span>1. Semai</span>
            </a>
            <a href="{{ route('peremajaan.index') }}" class="nav-link {{ request()->is('peremajaan*') ? 'active' : '' }}">
                <i class="fas fa-spa"></i>
                <span>2. Peremajaan</span>
            </a>
            <a href="{{ route('pendewasaan.index') }}" class="nav-link {{ request()->is('pendewasaan*') ? 'active' : '' }}">
                <i class="fas fa-leaf"></i>
                <span>3. Pendewasaan</span>
            </a>
            <a href="{{ route('panen.index') }}" class="nav-link {{ request()->is('panen*') ? 'active' : '' }}">
                <i class="fas fa-wheat-awn"></i>
                <span>4. Hasil Panen</span>
            </a>

            <span class="sidebar-section-title">Master Data</span>
            <a href="{{ route('tanaman.index') }}" class="nav-link {{ request()->is('tanaman*') ? 'active' : '' }}">
                <i class="fas fa-carrot"></i>
                <span>Jenis Tanaman</span>
            </a>
            <a href="{{ route('meja.index') }}" class="nav-link {{ request()->is('meja*') ? 'active' : '' }}">
                <i class="fas fa-table-cells-large"></i>
                <span>Meja Tanam</span>
            </a>
            <a href="{{ route('siklus.index') }}" class="nav-link {{ request()->is('siklus*') ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left"></i>
                <span>Durasi Siklus</span>
            </a>

            <span class="sidebar-section-title">Komunikasi & Laporan</span>
            <a href="{{ route('notifikasi-wa.index') }}" class="nav-link {{ request()->is('notifikasi-wa*') ? 'active' : '' }}">
                <i class="fab fa-whatsapp"></i>
                <span>Notifikasi WhatsApp</span>
            </a>
            <a href="{{ route('penerima_notif.index') }}" class="nav-link {{ request()->is('penerima_notif*') ? 'active' : '' }}">
                <i class="fas fa-address-book"></i>
                <span>Penerima Notif</span>
            </a>
            <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}">
                <i class="fas fa-file-waveform"></i>
                <span>Matriks Laporan</span>
            </a>

            @if(auth()->user()->level === 'admin')
            <span class="sidebar-section-title">Sistem</span>
            <a href="{{ route('user.index') }}" class="nav-link {{ request()->is('user*') ? 'active' : '' }}">
                <i class="fas fa-users-gear"></i>
                <span>Manajemen User</span>
            </a>
            @endif
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-none d-md-block">
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <div class="topbar-badge-live" title="ESP32 sedang mengirim data secara aktif">
                            <span class="live-dot"></span>
                            <span>ESP32 Online • Sinkronisasi Aktif</span>
                        </div>
                    @else
                        <div class="topbar-badge-offline" title="ESP32 belum mengirim data dalam 5 menit terakhir">
                            <span class="offline-dot"></span>
                            <span>ESP32 Offline • Sinkronisasi Mati ({{ $iotStatus['diff_text'] ?? 'Terputus' }})</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <small class="text-muted d-block" id="current-time">WIB</small>
                    <span class="badge badge-soft-success text-uppercase">{{ auth()->user()->level }}</span>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 rounded-3" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-emerald text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 700; background: #059669;">
                            {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                        </div>
                        <span class="fw-semibold small d-none d-sm-inline">{{ auth()->user()->username }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li class="px-3 py-2 border-bottom">
                            <span class="d-block small text-muted">Masuk sebagai</span>
                            <strong class="text-dark">{{ auth()->user()->username }}</strong>
                        </li>
                        <li><a class="dropdown-item py-2" href="{{ route('profil.index') }}"><i class="fas fa-id-card me-2 text-primary"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('profil.password') }}"><i class="fas fa-key me-2 text-warning"></i>Ganti Password</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"><i class="fas fa-arrow-right-from-bracket me-2"></i>Keluar (Logout)</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- CONTENT BODY -->
        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between mb-4 py-3" style="background-color: #ecfdf5; color: #065f46; border-left: 4px solid #10b981 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-circle-check fs-5 text-success"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mb-4 py-3" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-circle-exclamation fs-5 text-danger"></i>
                        <span>{{ session('error') ?? $errors->first() }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @else
    <main>
        @yield('content')
    </main>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Realtime clock
        function updateClock() {
            const el = document.getElementById('current-time');
            if (el) {
                const now = new Date();
                el.innerText = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) + ' ' +
                               now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Mobile sidebar toggle
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
