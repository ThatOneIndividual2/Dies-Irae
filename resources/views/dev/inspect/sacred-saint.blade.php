<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Saint inspect #{{ $saint->id }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 2rem; background: #140f0c; color: #ead9c1; }
        pre { background: #231910; padding: 1rem; overflow: auto; border: 1px solid #3d2c1e; }
        .warn { color: #d4a574; }
    </style>
</head>
<body>
    <p class="warn">Admin inspect. Canonization evidence is not a piety score.</p>
    <h1>{{ $saint->name }}</h1>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
