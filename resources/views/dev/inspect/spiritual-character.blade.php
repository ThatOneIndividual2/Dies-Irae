<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spiritual inspect #{{ $character->id }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 2rem; background: #140f0c; color: #ead9c1; }
        a { color: #c9a36a; }
        pre { background: #231910; padding: 1rem; overflow: auto; border: 1px solid #3d2c1e; }
        .warn { color: #d4a574; }
    </style>
</head>
<body>
    <p class="warn">Admin inspect. Interior scores are not player-facing.</p>
    <h1>Character #{{ $character->id }} {{ $character->first_name }}</h1>
    <p>World {{ $character->world_id }}</p>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
