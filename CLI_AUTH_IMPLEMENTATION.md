# CLI Auth Implementation

## 1. Summary
- Implemented a Laravel-managed device-style CLI auth flow backed by Sanctum.
- Added CLI login start, exchange, current-user, and logout API endpoints.
- Added browser verification routes and pages for approving pending CLI logins.
- Added secure CLI login transaction persistence with hashed device/user codes and lifecycle tracking.
- Added focused feature tests for the main success and failure paths.

## 2. Architecture
- Final chosen design: CLI device flow managed entirely by Laravel, with Google handled only server-side through Socialite and CLI API access handled with short-lived Sanctum bearer tokens.
- Why it was chosen:
  - It reuses the app’s existing Sanctum and Google login foundations.
  - It avoids embedding secrets in the Python CLI.
  - It avoids bearer tokens in browser redirect URLs.
  - It keeps browser session use limited to the browser approval step and keeps CLI API auth stateless.

## 3. Routes
- New API routes:
  - `POST /api/cli/auth/start`
  - `POST /api/cli/auth/exchange`
  - `GET /api/cli/auth/me`
  - `POST /api/cli/auth/logout`
- New web routes:
  - `GET /cli-auth/verify`
  - `GET /cli-auth/authorize/{deviceCode}`
  - `GET /cli-auth/requests/{loginRequest}`
  - `POST /cli-auth/requests/{loginRequest}/approve`
  - `GET /cli-auth/google/{loginRequest}/redirect`
  - `GET /cli-auth/google/callback`

## 4. Database Changes
- Added `cli_login_requests` table.
- Schema decisions:
  - device code stored as `device_code_hash`
  - user code stored as `user_code_hash`
  - explicit lifecycle status column
  - nullable `user_id` set on approval
  - JSON `requested_abilities`
  - expiry, approval, poll, and consumption timestamps
  - request metadata fields for client/device/IP/user agent
  - indexes on hashed codes and lifecycle/expiry lookups

## 5. Security Decisions
- Token handling
  - CLI uses dedicated Sanctum tokens with names prefixed `cli:`.
  - CLI tokens use explicit short expiry through per-token issuance.
  - Existing non-CLI flows keep their prior 7-day behavior explicitly in `AuthService`.
- Replay protection
  - Device codes and user codes are stored hashed.
  - A CLI login request can only be exchanged once because approved requests transition to `consumed`.
- Rate limiting
  - Separate limiters added for CLI start, exchange, and browser verification flows.
  - Exchange also enforces a per-request polling interval and returns `slow_down`.
- Expiry/revocation
  - Login requests expire server-side and transition to `expired`.
  - Logout revokes the current Sanctum token.
- OWASP-related considerations
  - No bearer tokens in redirect URLs.
  - No trust in public-client secrecy.
  - State-changing browser approval for already-authenticated users uses POST with CSRF protection.
  - Error responses avoid leaking secrets or provider details.

## 6. Files Changed
- `app/Models/CliLoginRequest.php`: CLI login transaction model.
- `app/Services/CliAuthService.php`: transaction lifecycle and exchange logic.
- `app/Services/AuthService.php`: explicit default token TTL and CLI token issuance.
- `app/Services/GoogleAuthService.php`: shared Google user resolution plus CLI-safe redirect/callback support.
- `app/Http/Controllers/Api/CliAuthController.php`: CLI auth API endpoints.
- `app/Http/Controllers/Web/CliAuthBrowserController.php`: browser verification and approval flow.
- `app/Http/Requests/CliAuth/StartCliAuthRequest.php`: start request validation.
- `app/Http/Requests/CliAuth/ExchangeCliAuthRequest.php`: exchange request validation.
- `app/Http/Requests/CliAuth/VerifyCliAuthRequest.php`: browser verify query validation.
- `app/Http/Resources/CliAuth/CliAuthStartResource.php`: start response shape.
- `app/Http/Resources/CliAuth/CliAuthExchangeResource.php`: exchange response shape.
- `app/Http/Resources/CliAuth/CliAuthMeResource.php`: current-user response shape.
- `database/migrations/2026_04_24_000001_create_cli_login_requests_table.php`: CLI login transaction schema.
- `routes/api.php`: new CLI API routes.
- `routes/web.php`: new browser verification routes.
- `app/Providers/RouteServiceProvider.php`: dedicated CLI auth rate limiters.
- `config/sanctum.php`: explicit default token TTL plus CLI auth config.
- `resources/views/cli-auth/verify.blade.php`: verification code entry page.
- `resources/views/cli-auth/request.blade.php`: approval page.
- `resources/views/cli-auth/status.blade.php`: success/error status page.
- `tests/Feature/CliAuthTest.php`: feature coverage for CLI auth.

## 7. API Contract
- `POST /api/cli/auth/start`
  - Request:
    ```json
    {
      "client_name": "hushstack-cli",
      "client_version": "1.0.0",
      "device_name": "alice-macbook",
      "requested_abilities": ["profile:read"]
    }
    ```
  - Response:
    ```json
    {
      "success": true,
      "data": {
        "device_code": "....",
        "user_code": "ABCD-EFGH",
        "verification_uri": "https://example.com/cli-auth/verify",
        "verification_uri_complete": "https://example.com/cli-auth/authorize/....",
        "interval": 5,
        "expires_in": 600
      }
    }
    ```
- `POST /api/cli/auth/exchange`
  - Request:
    ```json
    {
      "device_code": "...."
    }
    ```
  - Success response:
    ```json
    {
      "success": true,
      "data": {
        "access_token": "1|....",
        "token_type": "Bearer",
        "expires_at": "2026-04-24T10:30:00+00:00",
        "user": {
          "id": 1,
          "email": "user@example.com"
        }
      }
    }
    ```
  - Pending/error shape:
    ```json
    {
      "success": false,
      "error": "authorization_pending",
      "message": "Login not completed in browser yet."
    }
    ```
- `GET /api/cli/auth/me`
  - Response:
    ```json
    {
      "success": true,
      "data": {
        "user": {
          "id": 1,
          "email": "user@example.com"
        },
        "token": {
          "name": "cli:alice-macbook:20260424101500",
          "abilities": ["cli", "profile:read"],
          "expires_at": "2026-04-24T10:30:00+00:00"
        }
      }
    }
    ```
- `POST /api/cli/auth/logout`
  - Response:
    ```json
    {
      "success": true,
      "message": "CLI token revoked."
    }
    ```

## 8. Test Coverage
- Tested:
  - start login success
  - start login validation failure
  - exchange while pending
  - exchange after approval
  - exchange with invalid code
  - exchange after expiration
  - exchange after already consumed
  - logout success
  - unauthorized `me` and `logout`
  - browser approval path for an authenticated web user
  - start endpoint rate limiting
- Remaining gaps:
  - real Google redirect/callback integration is not exercised in feature tests
  - exchange `slow_down` behavior is not separately asserted
  - browser pages are tested through the approval path, not exhaustively for every status rendering branch

## 9. Follow-Up Recommendations
- Add a refresh-token flow only if you want longer-lived CLI sessions without frequent browser re-authentication.
- Add self-service token/session management so users can revoke active CLI sessions from the UI.
- Add audit logging around CLI start, approval, exchange, and revocation events.
- If you want true scope enforcement, add ability-aware middleware on the CLI-facing API routes that should be constrained.
