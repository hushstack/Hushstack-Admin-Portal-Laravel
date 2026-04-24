<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve CLI Login</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; margin: 0; }
        .card { max-width: 560px; margin: 64px auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.08); }
        .meta { color: #4b5563; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 12px 16px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-size: 16px; }
        .btn-primary { background: #111827; color: #fff; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Approve CLI Login</h1>
        <p class="meta">Client: {{ $loginRequest->client_name ?? 'Unknown client' }}{{ $loginRequest->client_version ? ' '.$loginRequest->client_version : '' }}</p>
        <p class="meta">Device: {{ $loginRequest->device_name ?? 'Unknown device' }}</p>

        @if ($isAuthenticated)
            <p>You are signed in as <strong>{{ $user?->email }}</strong>. Approve this request to continue in the CLI.</p>
            <form method="POST" action="{{ route('cli-auth.request.approve', $loginRequest) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Approve CLI Login</button>
            </form>
        @else
            <p>Continue with Google to approve this CLI login request.</p>
            <a class="btn btn-primary" href="{{ route('cli-auth.google.redirect', $loginRequest) }}">Continue with Google</a>
        @endif
    </div>
</body>
</html>
