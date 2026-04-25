<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLI Login</title>
    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(9, 18, 33, 0.8);
            --panel-border: rgba(255, 255, 255, 0.12);
            --text: #f7fafc;
            --muted: #99adc4;
            --primary: #67e8f9;
            --primary-strong: #22d3ee;
            --shadow: 0 28px 72px rgba(2, 8, 23, 0.46);
            --field-bg: rgba(255, 255, 255, 0.06);
            --field-border: rgba(255, 255, 255, 0.12);
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
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.24), transparent 30%),
                radial-gradient(circle at 85% 20%, rgba(99, 102, 241, 0.18), transparent 24%),
                linear-gradient(135deg, #040a14 0%, #0a1830 48%, #07111f 100%);
            display: grid;
            place-items: center;
            padding: 32px 20px;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            filter: blur(24px);
            opacity: 0.32;
            z-index: 0;
            animation: orbit 13s ease-in-out infinite;
        }

        body::before {
            top: -110px;
            right: -60px;
            background: rgba(34, 211, 238, 0.2);
        }

        body::after {
            bottom: -140px;
            left: -90px;
            background: rgba(99, 102, 241, 0.18);
            animation-delay: -5s;
        }

        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
            padding: 34px 32px;
            border-radius: 28px;
            background: var(--panel);
            border: 1px solid var(--panel-border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
            overflow: hidden;
            animation: lift 0.7s ease-out;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 40%, rgba(103, 232, 249, 0.05));
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #d8faff;
            font-size: 12px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #ecfeff);
            box-shadow: 0 0 16px rgba(103, 232, 249, 0.8);
        }

        h1 {
            margin: 18px 0 12px;
            font-size: clamp(2rem, 4vw, 2.8rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        p {
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 48ch;
        }

        .field {
            position: relative;
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #d9e8f5;
            font-size: 0.86rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        input {
            width: 100%;
            height: 62px;
            padding: 0 20px;
            border-radius: 18px;
            border: 1px solid var(--field-border);
            background: var(--field-bg);
            color: var(--text);
            font-size: 1.08rem;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        input::placeholder {
            color: #6f859e;
            letter-spacing: 0.18em;
        }

        input:focus {
            border-color: rgba(103, 232, 249, 0.6);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.14);
            transform: translateY(-1px);
        }

        button {
            width: 100%;
            min-height: 58px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #ecfeff, var(--primary-strong));
            color: #06202a;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 16px 36px rgba(34, 211, 238, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 42px rgba(34, 211, 238, 0.3);
        }

        .helper {
            margin-top: 16px;
            color: #8399b1;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        @keyframes lift {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes orbit {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(22px, 24px, 0) scale(1.06);
            }
        }

        @media (max-width: 640px) {
            .card {
                padding: 26px 22px;
                border-radius: 24px;
            }

            input {
                height: 58px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="eyebrow">CLI Authentication</span>
        <h1>Approve CLI Login</h1>
        <p>Enter the code shown in your terminal to continue with the browser-based verification flow and securely link this sign-in request.</p>
        <form method="GET" action="{{ route('cli-auth.verify') }}">
            <div class="field">
                <label for="user_code">Verification code</label>
                <input id="user_code" name="user_code" placeholder="ABCD-EFGH" autocomplete="one-time-code" spellcheck="false" required>
            </div>
            <button type="submit">Continue</button>
        </form>
        <p class="helper">The code is case-insensitive, but entering it exactly as shown makes verification faster and helps avoid mistakes.</p>
    </div>
</body>
</html>
