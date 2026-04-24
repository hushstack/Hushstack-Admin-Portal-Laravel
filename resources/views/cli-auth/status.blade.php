<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; margin: 0; }
        .card { max-width: 560px; margin: 64px auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.08); }
        .success { color: #065f46; }
        .error { color: #991b1b; }
        .info { color: #1d4ed8; }
        p { line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="{{ $kind }}">{{ $title }}</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
