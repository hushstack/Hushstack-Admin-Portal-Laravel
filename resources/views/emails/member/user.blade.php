<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>We received your member request</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(17,24,39,.08);">
                <tr>
                    <td style="padding:22px 26px;background:#1f6feb;color:#fff;">
                        <div style="font-size:16px;font-weight:700;">{{ config('app.name') }}</div>
                        <div style="margin-top:6px;font-size:13px;opacity:.95;">Thanks for your member request</div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:22px 26px;">
                        <p style="margin:0 0 12px;font-size:14px;line-height:1.7;">
                            Hi {{ $data['name'] }},<br>
                            We’ve received your member registration request. Our team will reach out to you soon.
                        </p>

                        <div style="margin-top:14px;padding:14px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;">
                            <div style="font-size:13px;font-weight:700;margin-bottom:8px;">Your message</div>
                            <div style="font-size:14px;line-height:1.7;white-space:pre-wrap;">{{ $data['message'] }}</div>
                        </div>

                        <p style="margin:16px 0 0;font-size:13px;color:#6b7280;line-height:1.7;">
                            We may contact you via the Telegram number you provided.
                        </p>

                        <p style="margin:18px 0 0;font-size:14px;line-height:1.7;">
                            Best regards,<br>
                            <strong>{{ config('app.name') }} Team</strong>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 26px;background:#f9fafb;border-top:1px solid #e5e7eb;">
                        <div style="font-size:12px;color:#6b7280;">
                            © {{ date('Y') }} {{ config('app.name') }} — {{ config('app.url') }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
