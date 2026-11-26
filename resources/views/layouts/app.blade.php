<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SIM Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-puskesmas.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}">
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="{{ asset('assets/img/logo-puskesmas.png') }}" alt="Logo Puskesmas Akelamo">
            <div>
                <b>Puskesmas Akelamo</b>
                <small>SMART HEALTH SYSTEM</small>
            </div>
        </div>
        <div class="nav-title">MENU UTAMA</div>
        <nav>
            @foreach ($menu as $key => $item)
                <a class="nav-item {{ ($crumb ?? '') === $key ? 'active' : '' }}" href="{{ \App\Support\ModuleRegistry::urlFor($key) }}">
                    <span>{{ $item[0] }}</span>{{ $item[1] }}
                </a>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            © {{ date('Y') }} Puskesmas Akelamo<br>Sistem Informasi Terintegrasi
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" id="mobile-toggle" aria-label="Buka menu">☰</button>
                <div class="crumb">Menu / <strong>{{ $pageTitle ?? 'Dashboard' }}</strong></div>
            </div>
            <div class="profile">
                <div class="notif-wrap">
                    <button class="notif-bell" id="notif-bell" type="button" aria-label="Notifikasi">🔔
                        @php ($unreadCount = \App\Models\AppNotification::unread()->count())
                        @if ($unreadCount > 0)<span class="notif-badge" id="notif-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                    </button>
                    <div class="notif-panel" id="notif-panel" hidden></div>
                </div>
                <a class="profile-link" href="{{ url('/profil') }}" title="Profil & ganti sandi">
                    <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->full_name, 0, 1)) }}</div>
                    <div>
                        <b>{{ auth()->user()->full_name }}</b>
                        <small>{{ auth()->user()->role }}</small>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" type="submit" title="Keluar">↪</button>
                </form>
            </div>
        </header>
        <div class="content">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
        <footer class="app-footer">
            <span>© {{ date('Y') }} Puskesmas Akelamo — Sistem Informasi Manajemen Puskesmas.</span>
            <span>
                Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
            </span>
        </footer>
    </main>
</div>
<div id="toast" role="status" aria-live="polite"></div>
</body>
</html>
