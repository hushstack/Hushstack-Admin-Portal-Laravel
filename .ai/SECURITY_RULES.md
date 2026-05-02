# Security Rules

## Secrets And Environment

- Never hardcode secrets, tokens, passwords, private keys, or credentials.
- Use `.env` for real secrets.
- Only update `.env.example` with placeholder values.
- Do not commit real values for OAuth, GitHub, Telegram, database, mail, Redis, S3, or R2 credentials.

## Input And Validation

- Validate input with Laravel Form Requests when possible.
- Validate query filters, pagination values, and uploaded files.
- Do not trust client-provided role, permission, owner, or user IDs without server-side checks.
- Keep file upload validation strict.

## Auth And Access Control

- Protect private routes with `auth:sanctum`.
- Protect admin routes with existing admin, super admin, role, or permission middleware.
- Use permission enum values for permission-protected routes.
- Check ownership for user-owned resources.
- Avoid IDOR/BOLA by verifying the authenticated user can access the requested resource.
- Keep public routes intentionally public and rate limited.

## Token Handling

- Handle Sanctum tokens safely.
- Revoke tokens when security-sensitive flows require it.
- Keep CLI auth tokens short-lived.
- Do not log access tokens, OTP values, passwords, or OAuth secrets.

## Error Handling

- Do not expose sensitive errors in API responses.
- Keep `APP_DEBUG=false` in production.
- Avoid revealing whether an email exists in password reset or OTP resend flows.

## Rate Limiting

- Keep rate limits on login, OTP, password reset, CLI auth, alerts, logs, and other sensitive endpoints.
- Add rate limits for new sensitive public endpoints.

## Dependency And Deployment

- Keep dependencies updated carefully.
- Review security impact before adding packages.
- Run tests for auth, permissions, ownership, and validation changes.
