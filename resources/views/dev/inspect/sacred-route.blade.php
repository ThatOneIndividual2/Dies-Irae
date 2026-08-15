<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pilgrimage route inspect #{{ $route->id }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 2rem; background: #140f0c; color: #ead9c1; }
        pre { background: #231910; padding: 1rem; overflow: auto; border: 1px solid #3d2c1e; }
    </style>
</head>
<body>
    <h1>{{ $route->name }}</h1>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
