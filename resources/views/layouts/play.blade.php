<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dies Irae')</title>
    <style>
        :root { --bg:#1b1410; --panel:#2a211a; --ink:#f3e6c8; --muted:#b9a48a; --accent:#9c1c1c; --link:#e2c48a; --ok:#7d9a6a; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Georgia, "Times New Roman", serif; background:var(--bg); color:var(--ink); }
        a { color:var(--link); }
        header { background:#120e0b; padding:12px 18px; border-bottom:2px solid #5a3b28; display:flex; justify-content:space-between; align-items:center; gap:12px; }
        header strong { letter-spacing:.08em; }
        nav { display:flex; flex-wrap:wrap; gap:8px 12px; font-size:14px; }
        main { padding:18px; max-width:1100px; }
        .flash { background:#3d2a12; border:1px solid #c9a15b; padding:8px 12px; margin-bottom:14px; }
        .err { background:#3d1515; border:1px solid #9c1c1c; padding:8px 12px; margin-bottom:14px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; }
        .card { background:var(--panel); border:1px solid #4a372c; padding:12px 14px; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:6px 8px; border-bottom:1px solid #4a372c; vertical-align:top; }
        button, .btn { background:var(--accent); color:#fff; border:0; padding:7px 12px; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-block; }
        select, input[type=number], input[type=email], input[type=password] { background:#120e0b; color:var(--ink); border:1px solid #5a3b28; padding:6px; }
        .muted { color:var(--muted); }
        svg.map { background:#120e0b; border:1px solid #4a372c; width:100%; max-width:640px; height:320px; }
        form.inline { display:inline; }
    </style>
</head>
<body>
<header>
    <strong>DIES IRAE</strong>
    @auth
        <nav>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('character') }}">Ruler</a>
            <a href="{{ route('dynasty') }}">Dynasty</a>
            <a href="{{ route('titles') }}">Titles</a>
            <a href="{{ route('realm') }}">Realm</a>
            <a href="{{ route('map') }}">Map</a>
            <a href="{{ route('church') }}">Church</a>
            <a href="{{ route('spiritual') }}">Spiritual</a>
            <a href="{{ route('plague') }}">Plague</a>
            <a href="{{ route('army') }}">Army</a>
            <a href="{{ route('apocalypse') }}">Apocalypse</a>
            <a href="{{ route('events') }}">Events</a>
            <form class="inline" method="post" action="{{ route('logout') }}">@csrf<button>Logout</button></form>
        </nav>
    @endauth
</header>
<main>
    @if(session('status'))<div class="flash" role="alert" aria-live="polite">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="err" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
</body>
</html>
