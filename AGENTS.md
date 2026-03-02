# AGENTS.md

Purpose: Project guardrails for security, performance, clean code, and maintainable Laravel practices.

## OWASP Top 10 (2021) Reminders
- A01 Broken Access Control: Enforce policies/guards on every privileged route and action.
- A02 Cryptographic Failures: Use Laravel Hash/Encrypt; never roll your own crypto; secure secrets.
- A03 Injection: Always validate, sanitize, and use query bindings/ORM.
- A04 Insecure Design: Add threat modeling for new features and verify abuse cases.
- A05 Security Misconfiguration: Harden env/config, disable debug in production.
- A06 Vulnerable and Outdated Components: Keep Composer/NPM deps updated and monitored.
- A07 Identification and Authentication Failures: Strong auth, MFA where required, safe session/token handling.
- A08 Software and Data Integrity Failures: Use signed builds, CI checks, and trusted packages only.
- A09 Security Logging and Monitoring Failures: Log auth/admin actions and alert on anomalies.
- A10 Server-Side Request Forgery (SSRF): Validate URLs and use allowlists for outbound requests.

## Laravel Security Checklist
- Use FormRequest validation for all inputs; never trust request data.
- Use authorization policies/gates or middleware for admin-only endpoints.
- Protect against mass assignment by limiting `$fillable` and avoiding `$guarded = []`.
- Use `Hash::make`, `Hash::check`, and `Crypt` for sensitive data.
- Sanitize file uploads and enforce MIME/type/size rules.
- Rate-limit auth and sensitive endpoints.
- Avoid leaking user existence in auth flows.

## Performance Guidance
- Prefer eager loading to avoid N+1 queries.
- Use pagination for lists, especially admin lists.
- Index foreign keys and frequently queried columns.
- Cache expensive queries and config where appropriate.
- Keep API payloads minimal and consistent.

## Clean Code and Structure
- Keep controllers thin; push business logic into Services/Actions.
- Use clear, consistent naming for routes, controllers, and models.
- Small methods, single responsibility, and predictable return shapes.
- Write tests for critical flows and authorization boundaries.
- Avoid duplication; extract shared logic into helpers or traits.