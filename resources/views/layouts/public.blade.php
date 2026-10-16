<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#052e16">
    <title>@yield('title') · {{ \App\Models\Setting::siteName() }}</title>
    @if ($logo = \App\Models\Setting::logoUrl())
        <link rel="icon" href="{{ $logo }}">
    @endif
    <meta name="description" content="@yield('description', 'Classifica, risultati, marcatori e pagelle del torneo.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="public">
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('home') }}">
                <x-brand-mark />
                <span class="brand-name">{{ \App\Models\Setting::siteName() }}</span>
            </a>
            <div class="user-box">
                @auth
                    <a class="btn small accent" href="{{ route('dashboard') }}">Gestionale</a>
                @else
                    <a class="btn small accent" href="{{ route('login') }}">Area riservata</a>
                @endauth
            </div>
        </div>
    </header>
    @yield('hero')
    <main>
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <x-brand-mark small />
            <span><strong>{{ \App\Models\Setting::siteName() }}</strong> · {{ now()->year }}</span>
            <span class="footer-links">
                <a href="#regole">Regole di gioco</a>
                @auth
                    <a href="{{ route('dashboard') }}">Gestionale</a>
                @else
                    <a href="{{ route('login') }}">Area riservata</a>
                @endauth
            </span>
        </div>
    </footer>
    <script>
        // Evidenzia nella barra delle sezioni quella che si sta leggendo.
        (() => {
            const links = [...document.querySelectorAll('.section-nav a')];
            if (!links.length || !('IntersectionObserver' in window)) return;
            const byId = new Map(links.map(a => [a.hash.slice(1), a]));
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    links.forEach(a => a.removeAttribute('aria-current'));
                    const link = byId.get(entry.target.id);
                    link.setAttribute('aria-current', 'true');
                    link.parentElement.scrollTo({ left: link.offsetLeft - 16, behavior: 'smooth' });
                });
            }, { rootMargin: '-30% 0px -60% 0px' });
            byId.forEach((a, id) => { const el = document.getElementById(id); if (el) observer.observe(el); });
        })();
    </script>
</body>
</html>
