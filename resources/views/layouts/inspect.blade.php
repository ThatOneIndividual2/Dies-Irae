<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dies Irae Inspect')</title>
    <style>
        :root {
            --bg: #140f0c;
            --panel: #1d1612;
            --ink: #e8dcc8;
            --muted: #9a8770;
            --accent: #8b1e1e;
            --line: #3a2c22;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Palatino Linotype", Palatino, "Book Antiqua", serif;
            background: var(--bg);
            color: var(--ink);
        }
        header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--line);
            background: var(--panel);
        }
        header a { color: var(--ink); text-decoration: none; }
        header strong { color: #c9a227; letter-spacing: 0.08em; }
        main { padding: 1.5rem; max-width: 1100px; }
        h1, h2 { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { text-align: left; padding: 0.4rem 0.6rem; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 500; }
        .ok { color: #7d9a6d; }
        .bad { color: #c45c5c; }
        .muted { color: var(--muted); }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('dev.inspect.index') }}"><strong>DIES IRAE</strong> inspect</a>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
