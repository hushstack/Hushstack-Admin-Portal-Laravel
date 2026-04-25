<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        :root {
            --bg: #04111b;
            --panel: rgba(7, 20, 35, 0.8);
            --panel-border: rgba(255, 255, 255, 0.12);
            --text: #f8fbff;
            --muted: #9ab0c5;
            --shadow: 0 26px 70px rgba(2, 8, 23, 0.46);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Helvetica Neue", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(59, 130, 246, 0.22), transparent 30%),
                radial-gradient(circle at 80% 18%, rgba(16, 185, 129, 0.16), transparent 22%),
                linear-gradient(140deg, #030712 0%, #071827 48%, #04111b 100%);
            display: grid;
            place-items: center;
            padding: 32px 20px;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            filter: blur(22px);
            opacity: 0.32;
            z-index: 0;
            animation: float 12s ease-in-out infinite;
        }

        body::before {
            top: -80px;
            right: -50px;
            background: rgba(59, 130, 246, 0.26);
        }

        body::after {
            bottom: -110px;
            left: -70px;
            background: rgba(16, 185, 129, 0.18);
            animation-delay: -5s;
        }

        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 620px;
            padding: 34px 32px;
            border-radius: 28px;
            background: var(--panel);
            border: 1px solid var(--panel-border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
            overflow: hidden;
            animation: enter 0.65s ease-out;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent 42%, rgba(255,255,255,0.04));
            pointer-events: none;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            border: 1px solid currentColor;
            background: rgba(255, 255, 255, 0.05);
        }

        .status-chip::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 14px currentColor;
        }

        h1 {
            margin: 18px 0 12px;
            font-size: clamp(2rem, 4vw, 2.8rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.75;
            max-width: 54ch;
        }

        .success {
            color: #4ade80;
        }

        .error {
            color: #fb7185;
        }

        .info {
            color: #7dd3fc;
        }

        @keyframes enter {
            from {
                opacity: 0;
                transform: translateY(22px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(18px, 24px, 0) scale(1.05);
            }
        }

        @media (max-width: 640px) {
            .card {
                padding: 26px 22px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="status-chip {{ $kind }}">
            @if ($kind === 'success')
                Approved
            @elseif ($kind === 'error')
                Action Needed
            @else
                Authentication Status
            @endif
        </span>
        <h1 class="{{ $kind }}">{{ $title }}</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
