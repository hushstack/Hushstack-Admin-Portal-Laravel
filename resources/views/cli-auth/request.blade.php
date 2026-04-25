<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve CLI Login</title>
    <style>
        :root {
            --bg: #07111f;
            --bg-soft: #10233d;
            --panel: rgba(9, 18, 33, 0.78);
            --panel-border: rgba(255, 255, 255, 0.12);
            --text: #f7fafc;
            --muted: #9cb0c7;
            --primary: #7dd3fc;
            --primary-strong: #38bdf8;
            --primary-deep: #0f172a;
            --accent: #f59e0b;
            --shadow: 0 30px 80px rgba(2, 8, 23, 0.45);
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
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.30), transparent 34%),
                radial-gradient(circle at 85% 15%, rgba(245, 158, 11, 0.20), transparent 22%),
                linear-gradient(135deg, #050b16 0%, #0b1730 45%, #07111f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: auto;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            filter: blur(28px);
            opacity: 0.35;
            z-index: 0;
            animation: drift 14s ease-in-out infinite;
        }

        body::before {
            top: -120px;
            right: -80px;
            background: rgba(56, 189, 248, 0.22);
        }

        body::after {
            bottom: -160px;
            left: -100px;
            background: rgba(245, 158, 11, 0.16);
            animation-delay: -6s;
        }

        .shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 620px;
        }

        .card {
            position: relative;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            padding: 32px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
            animation: rise 0.7s ease-out;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 40%, rgba(125, 211, 252, 0.06));
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #d8f3ff;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #e0f2fe);
            box-shadow: 0 0 16px rgba(125, 211, 252, 0.8);
        }

        h1 {
            margin: 18px 0 12px;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        .lead {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.7;
            max-width: 52ch;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .meta-card {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .meta-label {
            display: block;
            margin-bottom: 8px;
            color: #8aa2ba;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .meta-value {
            margin: 0;
            color: var(--text);
            font-size: 1rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .account-box {
            margin-bottom: 24px;
            padding: 18px 20px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(125, 211, 252, 0.14), rgba(255, 255, 255, 0.04));
            border: 1px solid rgba(125, 211, 252, 0.18);
            color: #dff8ff;
            line-height: 1.6;
        }

        .account-box strong {
            color: #ffffff;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .btn {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 56px;
            padding: 0 22px;
            border-radius: 18px;
            border: 1px solid transparent;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.98rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            color: var(--primary-deep);
            background: linear-gradient(135deg, #e0f2fe, var(--primary));
            box-shadow: 0 16px 34px rgba(56, 189, 248, 0.28);
        }

        .btn-primary:hover {
            box-shadow: 0 22px 40px rgba(56, 189, 248, 0.32);
        }

        .btn-google {
            color: var(--text);
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .btn-google:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-icon {
            width: 24px;
            height: 24px;
            display: inline-grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.14);
            font-size: 0.95rem;
            font-weight: 700;
        }

        .footer-note {
            margin-top: 20px;
            color: #8398af;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes drift {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(18px, 26px, 0) scale(1.06);
            }
        }

        @media (max-width: 640px) {
            .card {
                padding: 24px;
                border-radius: 24px;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .actions,
            .actions form {
                width: 100%;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="card">
            <span class="eyebrow">Secure Browser Verification</span>
            <h1>Approve CLI Login</h1>
            <p class="lead">Review this authentication request and continue only if this sign-in was started by you from a trusted terminal session.</p>

            <div class="meta-grid">
                <div class="meta-card">
                    <span class="meta-label">Client</span>
                    <p class="meta-value">{{ $loginRequest->client_name ?? 'Unknown client' }}{{ $loginRequest->client_version ? ' '.$loginRequest->client_version : '' }}</p>
                </div>
                <div class="meta-card">
                    <span class="meta-label">Device</span>
                    <p class="meta-value">{{ $loginRequest->device_name ?? 'Unknown device' }}</p>
                </div>
            </div>

            @if ($isAuthenticated)
                <div class="account-box">
                    You are signed in as <strong>{{ $user?->email }}</strong>. Approving will send a secure confirmation back to your CLI session.
                </div>

                <div class="actions">
                    <form method="POST" action="{{ route('cli-auth.request.approve', $loginRequest) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <span class="btn-icon">✓</span>
                            Approve CLI Login
                        </button>
                    </form>
                </div>
            @else
                <div class="account-box">
                    Sign in with your Google account to verify your identity before approving this CLI login request.
                </div>

                <div class="actions">
                    <a class="btn btn-google" href="{{ route('cli-auth.google.redirect', $loginRequest) }}">
                        <span class="btn-icon">G</span>
                        Continue with Google
                    </a>
                </div>
            @endif

            <p class="footer-note">If you did not initiate this sign-in, you can safely close this page and ignore the request.</p>
        </section>
    </main>
</body>
</html>
