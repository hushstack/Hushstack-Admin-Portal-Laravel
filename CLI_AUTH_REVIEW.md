# CLI Auth Review

## 1. Existing Auth Components
- `composer.json`
  - Reusable: `laravel/sanctum` is already installed for bearer-token API auth.
  - Reusable: `laravel/socialite` is already installed and configured for Google login.
  - Relevant absence: `laravel/passport` and any JWT auth package are not installed.
- `routes/api.php`
  - Reusable: `/api/auth/google/redirect` and `/api/auth/google/callback` already exist.
  - Reusable: most protected API routes already use `auth:sanctum`, which is the right guard for a CLI bearer token.
  - Reusable: `/api/auth/logout` already revokes the current Sanctum token.
  - Gap: there is no CLI-specific login initiation, login completion, polling, refresh, or `whoami` endpoint.
- `app/Http/Controllers/Api/GoogleAuthController.php`
  - Reusable: already delegates Google login to Laravel and issues a Sanctum token after successful callback.
  - Gap: current callback can redirect to `redirect_to` with `token` in the query string. That is not appropriate for a public CLI because browser URL leakage is a real risk.
- `app/Services/GoogleAuthService.php`
  - Reusable: already owns Google Socialite redirect/callback handling, user lookup, user creation, provider linking, state signing, and redirect allowlisting.
  - Reusable: if Google account email is verified, it links or creates a local `users` record and marks it verified.
  - Gap: current signed `state` is only for Google redirect integrity; it is not a CLI login transaction model and does not support secure post-browser token exchange.
- `app/Http/Controllers/Api/AuthController.php`
  - Reusable: issues Sanctum tokens for email/password login and deletes the current token on logout.
  - Reusable: password reset currently revokes all user tokens, which is a useful precedent for CLI token revocation behavior.
  - Gap: `issueToken()` currently produces a generic token name `api` with no CLI scoping, device metadata, or explicit TTL per token.
- `app/Services/AuthService.php`
  - Reusable: central place that mints Sanctum tokens via `$user->createToken(...)->plainTextToken`.
  - Gap: does not currently set token abilities or per-token expiry, and does not distinguish CLI tokens from browser/app tokens.
- `app/Models/User.php`
  - Reusable: uses `Laravel\Sanctum\HasApiTokens`.
  - Reusable: contains `provider`, `provider_id`, `social_login_key`, `email_verified_at`, `role_id`, and role/permission helpers.
  - Reusable: role-aware helpers mean CLI can immediately consume the same authorization model as the existing API.
- `config/sanctum.php`
  - Reusable: Sanctum bearer-token auth is active.
  - Important current behavior: global token expiration is `60 * 24 * 7` minutes, which is 7 days.
  - Important current behavior: `EnsureFrontendRequestsAreStateful` is commented out in `app/Http/Kernel.php`, so this API is already leaning toward stateless bearer-token use, which is good for CLI.
- `config/services.php`
  - Reusable: `google.client_id`, `google.client_secret`, and `google.redirect` are already configured for Laravel-side Google auth.
  - Reusable: `frontend_redirect_whitelist` exists and can be adapted or paralleled with a CLI callback/finish allowlist if you support loopback callbacks.
- `database/migrations/2014_10_12_000000_create_users_table.php`
  - Reusable: `provider` and `provider_id` columns already support social-login linkage.
- `database/migrations/2026_02_26_000001_add_social_login_key_to_users_table.php`
  - Reusable: confirms the app already distinguishes social-login-related identity data, though this specific column is not enough for CLI auth.
- `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`
  - Reusable: Sanctum PAT table already exists and is the right starting point for CLI access tokens.
  - Gap: there is no separate refresh-token or CLI login transaction table.
- `app/Http/Resources/UserResource.php`
  - Reusable: good basis for a CLI `whoami` response.
- `app/Models/Role.php`, `database/migrations/2026_03_02_000001_create_roles_table.php`, `database/migrations/2026_03_02_000002_add_role_id_to_users_table.php`, `database/migrations/2026_04_10_000001_create_permissions_table.php`, `database/migrations/2026_04_10_000002_create_role_permissions_table.php`
  - Reusable: existing role and permission model can be applied unchanged to CLI-issued tokens and CLI API calls.

## 2. Recommended Architecture
- Best fit: keep Laravel as the only auth authority and add a Laravel-managed device-style browser login flow for the CLI, backed by Sanctum personal access tokens.
- Why this fits this repo:
  - The project already uses Sanctum bearer tokens and `auth:sanctum` everywhere.
  - Google login already terminates in Laravel through Socialite, which matches your constraint that Python must not talk directly to Google OAuth.
  - A public pip-installed CLI is a public client, so you should not embed secrets and should avoid handing final tokens through browser query strings.
  - A device-style flow works without local callback servers, works in remote shells/SSH, and keeps Google OAuth entirely inside Laravel.
- Recommended flow:
  - CLI calls Laravel `POST /api/cli/auth/start`.
  - Laravel creates a short-lived login transaction and returns:
    - `verification_uri`
    - `browser_url`
    - `user_code`
    - `device_code`
    - `interval`
    - `expires_in`
  - CLI opens `browser_url`.
  - Browser hits a Laravel web route such as `/cli-auth/authorize/{device_code}` or `/cli-auth/verify?user_code=...`.
  - If the browser user is not yet authenticated in Laravel, that page redirects into the existing Google login flow.
  - After Google callback, Laravel attaches the authenticated user to the login transaction and marks it approved.
  - CLI polls `POST /api/cli/auth/exchange` with the `device_code` until approved.
  - When approved, Laravel issues a short-lived Sanctum access token and, if you want silent renewal, a refresh token stored server-side in a dedicated table.
- Why not reuse the current Google callback directly for CLI:
  - Today `GoogleAuthController` can redirect with `?token=...` in the URL.
  - That leaks bearer tokens into browser history, logs, analytics, crash reports, referrers, and potentially shell history if copied.
  - It also assumes a redirect target rather than a CLI-safe pending transaction.
- PKCE note:
  - If you prefer a loopback callback flow instead of device flow, use Laravel as the OAuth-style authorization server and require PKCE.
  - In this codebase, device flow is cleaner than Passport-style auth-code + PKCE because Sanctum is already in place and most of your API is first-party.
- Recommendation: use Sanctum, not Passport and not JWT.
  - Sanctum:
    - Already installed and already used by the API.
    - Simple server-side revocation via `personal_access_tokens`.
    - Good fit when Laravel itself is the only issuer and verifier.
    - Easy to constrain by abilities and token naming.
  - Passport:
    - Better only if you want Laravel to become a full OAuth2 authorization server for many external clients and standardized auth-code + PKCE flows.
    - Heavier operationally than this app currently needs.
    - Duplicates capability you do not need if the only public client is your own CLI.
  - JWT:
    - Worse revocation story unless you add substantial state anyway.
    - Encourages long-lived self-contained tokens, which is the opposite of your stated preference.
    - Does not align with the repo’s current Sanctum-based auth model.

## 3. Required Backend Changes
- Routes/endpoints to add
  - `POST /api/cli/auth/start`
  - `GET /cli-auth/verify`
  - `GET /cli-auth/authorize/{deviceCode}` or `GET /cli-auth/verify/{userCode}`
  - `POST /api/cli/auth/exchange`
  - `GET /api/cli/auth/me`
  - `POST /api/cli/auth/logout`
  - Optional if refresh is implemented: `POST /api/cli/auth/refresh`
  - Optional admin/self-service: `GET /api/cli/auth/tokens`, `DELETE /api/cli/auth/tokens/{id}`
- Controllers/services to add or modify
  - Add `app/Http/Controllers/Api/CliAuthController.php`
    - `start()`
    - `exchange()`
    - `me()`
    - `logout()`
    - optional `refresh()`
  - Add `app/Http/Controllers/Web/CliAuthBrowserController.php`
    - render approval page
    - redirect unauthenticated browser users into Google login
    - finalize approved login transaction after Laravel-side auth completes
  - Add `app/Services/CliAuthService.php`
    - create login transactions
    - bind browser-authenticated user to transaction
    - issue CLI access token
    - issue/rotate refresh token if enabled
    - revoke token(s)
  - Modify `app/Services/AuthService.php`
    - add a dedicated method for issuing CLI access tokens with name, abilities, and per-token expiry
  - Modify `app/Http/Controllers/Api/GoogleAuthController.php` and `app/Services/GoogleAuthService.php`
    - preserve existing web behavior
    - add support for resuming a pending CLI login transaction after Google callback instead of returning bearer token in URL
    - remove or deprecate the redirect-with-token pattern for CLI use
- Database changes
  - Add `cli_login_requests` table
    - columns:
      - `id`
      - `device_code_hash` unique
      - `user_code_hash` unique
      - `status` enum/string: `pending`, `approved`, `consumed`, `denied`, `expired`
      - `user_id` nullable FK to `users`
      - `requested_scopes` nullable json
      - `pkce_challenge` nullable string if you want proof binding on exchange
      - `approved_at`, `expires_at`, `last_polled_at`, `consumed_at`
      - `client_name`, `client_version`, `ip_address`, `user_agent`
  - Add `cli_refresh_tokens` table if refresh is needed
    - columns:
      - `id`
      - `user_id` FK
      - `personal_access_token_id` nullable FK to `personal_access_tokens`
      - `token_hash` unique
      - `name`
      - `abilities` nullable json
      - `expires_at`
      - `last_used_at`
      - `revoked_at`
      - `rotated_from_id` nullable self FK
      - timestamps
  - Optional: create a custom Sanctum token model or at least use token naming conventions like `cli:hostname:timestamp`
- Middleware changes
  - Keep CLI API endpoints stateless and bearer-token-only.
  - Protect `me`, `logout`, and optional `refresh` with `auth:sanctum`.
  - Add aggressive rate limiting to `start` and `exchange`.
  - Browser verification routes should use `web` middleware, not `auth:sanctum`.
- Config/env changes
  - Add `CLI_AUTH_DEVICE_CODE_TTL=600`
  - Add `CLI_AUTH_POLL_INTERVAL=5`
  - Add `CLI_AUTH_ACCESS_TOKEN_TTL_MINUTES=15`
  - Add `CLI_AUTH_REFRESH_TOKEN_TTL_DAYS=30` if refresh enabled
  - Add `CLI_AUTH_BROWSER_BASE_URL` or rely on `APP_URL`
  - Consider `SANCTUM_TOKEN_PREFIX=hsa_` or similar for secret scanning
  - Consider tightening `config/sanctum.php` global expiration if web/mobile clients do not need 7-day PATs

## 4. Proposed API Contract
- Start login
  - method: `POST`
  - path: `/api/cli/auth/start`
  - auth requirement: none
  - request body/query:
    ```json
    {
      "client_name": "hushstack-cli",
      "client_version": "1.4.2",
      "device_name": "alice-macbook",
      "requested_scopes": ["profile:read"]
    }
    ```
  - response JSON example:
    ```json
    {
      "success": true,
      "data": {
        "device_code": "dc_7nA0m5Q...",
        "user_code": "HUSH-4K9C",
        "verification_uri": "https://admin.example.com/cli-auth/verify",
        "verification_uri_complete": "https://admin.example.com/cli-auth/authorize/dc_7nA0m5Q...",
        "expires_in": 600,
        "interval": 5
      }
    }
    ```
  - error cases
    - `429` if rate limited
    - `422` if invalid payload
- Complete/exchange login
  - method: `POST`
  - path: `/api/cli/auth/exchange`
  - auth requirement: none
  - request body/query:
    ```json
    {
      "device_code": "dc_7nA0m5Q..."
    }
    ```
    Optional if you bind the CLI more tightly:
    ```json
    {
      "device_code": "dc_7nA0m5Q...",
      "code_verifier": "random-high-entropy-string"
    }
    ```
  - response JSON example when approved:
    ```json
    {
      "success": true,
      "data": {
        "access_token": "1|sanctumplaintexttoken",
        "token_type": "Bearer",
        "expires_at": "2026-04-24T10:30:00Z",
        "refresh_token": "rt_8jM3...",
        "refresh_expires_at": "2026-05-24T10:15:00Z",
        "user": {
          "id": 12,
          "first_name": "Alice",
          "last_name": "Lee",
          "username": "alice_7f2d",
          "email": "alice@example.com",
          "provider": "google",
          "role_id": 1,
          "role": {
            "id": 1,
            "name": "User",
            "slug": "user"
          }
        }
      }
    }
    ```
  - response JSON example while pending:
    ```json
    {
      "success": false,
      "error": "authorization_pending",
      "message": "Login not completed in browser yet."
    }
    ```
  - error cases
    - `400` `authorization_pending`
    - `400` `slow_down`
    - `401` `invalid_device_code`
    - `401` `invalid_code_verifier` if PKCE-like proof is used
    - `410` `expired_token`
    - `409` `already_consumed`
    - `429` if polling too aggressively
- Current user
  - method: `GET`
  - path: `/api/cli/auth/me`
  - auth requirement: `auth:sanctum`
  - request body/query: none
  - response JSON example:
    ```json
    {
      "success": true,
      "data": {
        "user": {
          "id": 12,
          "first_name": "Alice",
          "last_name": "Lee",
          "username": "alice_7f2d",
          "email": "alice@example.com",
          "provider": "google",
          "role_id": 1,
          "role": {
            "id": 1,
            "name": "User",
            "slug": "user"
          }
        },
        "token": {
          "name": "cli:alice-macbook:2026-04-24T10:15:00Z",
          "abilities": ["profile:read"],
          "expires_at": "2026-04-24T10:30:00Z"
        }
      }
    }
    ```
  - error cases
    - `401` unauthenticated
    - `403` token lacks required ability if abilities are enforced
- Logout/revoke token
  - method: `POST`
  - path: `/api/cli/auth/logout`
  - auth requirement: `auth:sanctum`
  - request body/query:
    ```json
    {
      "revoke_refresh_token": true
    }
    ```
  - response JSON example:
    ```json
    {
      "success": true,
      "message": "CLI token revoked."
    }
    ```
  - error cases
    - `401` unauthenticated
    - `422` invalid request body
- Optional refresh token
  - method: `POST`
  - path: `/api/cli/auth/refresh`
  - auth requirement: none
  - request body/query:
    ```json
    {
      "refresh_token": "rt_8jM3..."
    }
    ```
  - response JSON example:
    ```json
    {
      "success": true,
      "data": {
        "access_token": "2|newtoken",
        "token_type": "Bearer",
        "expires_at": "2026-04-24T10:45:00Z",
        "refresh_token": "rt_9pQ1...",
        "refresh_expires_at": "2026-05-24T10:30:00Z"
      }
    }
    ```
  - error cases
    - `401` invalid refresh token
    - `401` revoked refresh token
    - `410` expired refresh token
    - `409` replayed rotated refresh token

## 5. Security Notes
- Public client risks
  - The pip-installed CLI must be treated as untrusted. Do not put Google secrets or Laravel client secrets in it.
  - Do not use the current browser redirect pattern that returns `token` in the URL query.
  - Assume device codes, user codes, and refresh tokens can be stolen from a compromised workstation; keep them short-lived and revocable.
- Token storage considerations
  - Python should store access and refresh tokens in OS-native secure storage if possible.
  - If file storage is unavoidable, use user-only permissions and avoid writing tokens into shell history or logs.
  - Sanctum tokens are only shown once; Laravel stores only the hash in `personal_access_tokens`, which is good.
- Expiration / refresh strategy
  - Access token: short-lived, ideally 10 to 15 minutes.
  - Refresh token: optional, 30 days max, one-time rotation on every refresh.
  - If you want a simpler first cut, skip refresh tokens and force browser re-auth when the short-lived access token expires.
  - Do not keep the current 7-day generic CLI bearer token behavior.
- CSRF/session caveats
  - CLI auth must not rely on Laravel session cookies for API usage.
  - Browser routes may use Laravel web session during the human sign-in step only.
  - After approval, the CLI should authenticate exclusively with `Authorization: Bearer ...`.
- Revocation strategy
  - `POST /api/cli/auth/logout` should revoke the current access token.
  - If refresh exists, revoke the linked refresh token too.
  - Password reset and account deletion already revoke tokens in current code; extend that to revoke CLI refresh tokens as well.
  - Consider a “logout all CLI sessions” endpoint later.
- Rate limiting
  - `start`: limit by IP and maybe by unauthenticated fingerprint.
  - `exchange`: strict poll interval enforcement with `slow_down`.
  - Browser verify endpoints: rate limit by IP and user code attempts.
- PKCE if relevant
  - For pure device flow, PKCE is not mandatory, but adding a `code_verifier`/`code_challenge` pair improves proof-of-possession at exchange time.
  - If you choose local-loopback browser callback instead of device flow, PKCE should be mandatory.

## 6. Python CLI Integration Steps
- `login`
  - CLI `POST`s to `/api/cli/auth/start`.
  - CLI prints the verification URL and user code, and tries to open the browser automatically.
  - User completes Google sign-in in the browser, but only with Laravel pages and Laravel routes.
  - CLI polls `/api/cli/auth/exchange` every `interval` seconds until it receives tokens or an expiry/error.
  - CLI stores `access_token`, optional `refresh_token`, and expiry metadata locally.
- Authenticated API usage
  - CLI sends `Authorization: Bearer <access_token>` to existing Laravel API routes already protected by `auth:sanctum`.
  - For a simple identity check, CLI calls `/api/cli/auth/me`.
  - Existing role/permission middleware continues to work because the authenticated principal is still `App\Models\User`.
- Token renewal
  - If refresh is implemented, CLI refreshes shortly before access token expiry.
  - If refresh fails because token is revoked/expired/replayed, CLI falls back to full browser login.
  - If refresh is not implemented initially, CLI falls back directly to full browser login on 401 or expiry.
- `logout`
  - CLI calls `/api/cli/auth/logout` with the current bearer token.
  - CLI deletes local token state after successful response.
  - If refresh token exists, CLI should delete that local state too.

## 7. Recommended Implementation Plan
- Phase 1
  - Keep Sanctum.
  - Add `cli_login_requests` table.
  - Add CLI auth start/exchange/me/logout endpoints.
  - Add browser verification routes and controller.
  - Reuse existing Google login, but change post-login completion so it approves the pending CLI transaction instead of putting bearer tokens in redirect query params.
- Phase 2
  - Add short-lived CLI access tokens with explicit token names and abilities.
  - Tighten token TTL from the current generic 7-day behavior for CLI-issued tokens.
  - Add tests for pending, approved, expired, denied, and replayed login transactions.
- Phase 3
  - Add refresh-token rotation if you want better UX than frequent browser re-login.
  - Add token-management endpoints and UI for users to revoke their own CLI sessions.
- Phase 4
  - Add audit logging for CLI login start, approval, exchange, refresh, and revoke events.
  - Add anomaly detection or alerts if the same refresh token is replayed or if polling is abusive.

## 8. Open Questions
- I did not inspect the actual `.env`, so I cannot confirm the production values for `GOOGLE_REDIRECT_URI`, `APP_URL`, `FRONTEND_REDIRECT_WHITELIST`, or Sanctum token prefix settings.
- I did not find any existing dedicated `whoami` endpoint; the closest reusable shape is `UserResource`, plus some profile endpoints under `/api/profile/*`.
- The current codebase has Google and Microsoft social login, but I only traced the Google path because that is the requested reuse path.
- `GoogleAuthController` and `GoogleAuthService` currently support redirect-based token return. If that is already used by another frontend, implementation needs to preserve that behavior for web clients while adding a separate CLI-safe path.
- The current API does not appear to use Sanctum abilities in route middleware; if you want scope-like CLI restrictions, that would be a new convention to introduce.
- I did not find an existing custom Sanctum `PersonalAccessToken` model; if you want richer token metadata, that may be worth adding.
- I did not inspect deployment/proxy settings, so loopback callback feasibility versus pure device flow should still be validated against your actual deployment topology.

## 9. Files To Show Codex
- `app/Http/Controllers/Api/GoogleAuthController.php`
- `app/Services/GoogleAuthService.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Services/AuthService.php`
- `routes/api.php`
- `routes/web.php`
- `app/Http/Kernel.php`
- `config/sanctum.php`
- `config/services.php`
- `config/auth.php`
- `app/Models/User.php`
- `app/Http/Resources/UserResource.php`
- `database/migrations/2014_10_12_000000_create_users_table.php`
- `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`
- `database/migrations/2026_02_26_000001_add_social_login_key_to_users_table.php`
- `app/Models/Role.php`
- `database/migrations/2026_03_02_000001_create_roles_table.php`
- `database/migrations/2026_03_02_000002_add_role_id_to_users_table.php`
