<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLI Login</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; margin: 0; }
        .card { max-width: 480px; margin: 64px auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.08); }
        input, button { width: 100%; padding: 12px 14px; font-size: 16px; border-radius: 10px; }
        input { border: 1px solid #d0d0d0; margin: 16px 0; }
        button { border: 0; background: #111827; color: #fff; cursor: pointer; }
        p { color: #4b5563; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Approve CLI Login</h1>
        <p>Enter the code shown in your terminal to continue with browser-based sign-in.</p>
        <form method="GET" action="{{ route('cli-auth.verify') }}">
            <input name="user_code" placeholder="ABCD-EFGH" autocomplete="one-time-code" required>
            <button type="submit">Continue</button>
        </form>
    </div>
</body>
</html>
