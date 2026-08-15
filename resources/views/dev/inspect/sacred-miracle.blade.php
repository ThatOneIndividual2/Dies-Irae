<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Miracle inspect #{{ $miracle->id }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 2rem; background: #140f0c; color: #ead9c1; }
        pre { background: #231910; padding: 1rem; overflow: auto; border: 1px solid #3d2c1e; }
        .warn { color: #d4a574; }
    </style>
</head>
<body>
    <p class="warn">Recognition is not mechanical proof of causation.</p>
    <h1>Miracle #{{ $miracle->id }}</h1>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
