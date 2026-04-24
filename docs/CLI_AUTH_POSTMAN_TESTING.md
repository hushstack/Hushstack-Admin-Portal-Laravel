# CLI Auth Postman Testing

## Purpose
- Review the implemented CLI auth flow.
- Test the API endpoints in Postman.
- Follow the browser approval flow step by step.

## Base URL
- Use your Laravel app base URL, for example:
  - `http://127.0.0.1:8000`

## Endpoints
- `POST /api/cli/auth/start`
- `POST /api/cli/auth/exchange`
- `GET /api/cli/auth/me`
- `POST /api/cli/auth/logout`
- `GET /cli-auth/verify`
- `GET /cli-auth/authorize/{deviceCode}`

## Postman Collection Setup
- Create a Postman environment with:
  - `base_url`
  - `device_code`
  - `user_code`
  - `access_token`

## 1. Start Login
- Method: `POST`
- URL: `{{base_url}}/api/cli/auth/start`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body:
```json
{
  "client_name": "hushstack-cli",
  "client_version": "1.0.0",
  "device_name": "my-laptop",
  "requested_abilities": ["profile:read"]
}
```
- Expected response:
```json
{
  "success": true,
  "data": {
    "device_code": "...",
    "user_code": "ABCD-EFGH",
    "verification_uri": "http://127.0.0.1:8000/cli-auth/verify",
    "verification_uri_complete": "http://127.0.0.1:8000/cli-auth/authorize/...",
    "interval": 5,
    "expires_in": 600
  }
}
```
- Save:
  - `data.device_code` -> `device_code`
  - `data.user_code` -> `user_code`

## 2. Test Exchange While Still Pending
- Method: `POST`
- URL: `{{base_url}}/api/cli/auth/exchange`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body:
```json
{
  "device_code": "{{device_code}}"
}
```
- Expected response before browser approval:
```json
{
  "success": false,
  "error": "authorization_pending",
  "message": "Login not completed in browser yet."
}
```

## 3. Browser Approval Flow
- Open one of these URLs in your browser:
  - `{{base_url}}/cli-auth/authorize/{{device_code}}`
  - or `{{base_url}}/cli-auth/verify`
- If using `/cli-auth/verify`:
  - enter `{{user_code}}`
  - continue to the approval page
- Expected flow:
  - Laravel shows the CLI approval page
  - if not already signed in on the browser, it sends you through Google login
  - after successful Laravel-side sign-in, approve the CLI request
  - Laravel shows a success page telling you to return to the terminal

## 4. Exchange After Approval
- Method: `POST`
- URL: `{{base_url}}/api/cli/auth/exchange`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body:
```json
{
  "device_code": "{{device_code}}"
}
```
- Expected response:
```json
{
  "success": true,
  "data": {
    "access_token": "1|...",
    "token_type": "Bearer",
    "expires_at": "2026-04-24T10:30:00+00:00",
    "user": {
      "id": 1,
      "email": "user@example.com"
    }
  }
}
```
- Save:
  - `data.access_token` -> `access_token`

## 5. Test Current User
- Method: `GET`
- URL: `{{base_url}}/api/cli/auth/me`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer {{access_token}}`
- Expected response:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com"
    },
    "token": {
      "name": "cli:my-laptop:20260424101500",
      "abilities": ["cli", "profile:read"],
      "expires_at": "2026-04-24T10:30:00+00:00"
    }
  }
}
```

## 6. Test Logout
- Method: `POST`
- URL: `{{base_url}}/api/cli/auth/logout`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer {{access_token}}`
- Expected response:
```json
{
  "success": true,
  "message": "CLI token revoked."
}
```

## 7. Verify Token Is Revoked
- Call `GET {{base_url}}/api/cli/auth/me` again with the same bearer token.
- Expected response:
  - `401 Unauthorized`

## Error Cases To Test
- Invalid start payload
  - send invalid `requested_abilities`
  - expect `422`
- Invalid device code
  - send fake `device_code` to `/api/cli/auth/exchange`
  - expect `401`
- Expired device code
  - start login and wait until it expires
  - then exchange
  - expect `410`
- Already consumed device code
  - exchange once successfully
  - exchange again with the same `device_code`
  - expect `409`
- Rate limit
  - call `/api/cli/auth/start` more than 5 times in a minute from the same IP
  - expect `429`

## Recommended Manual Test Flow
1. Start Laravel locally.
2. In Postman, call `POST /api/cli/auth/start`.
3. Confirm you receive `device_code`, `user_code`, and verification URLs.
4. In Postman, call `POST /api/cli/auth/exchange` immediately and confirm `authorization_pending`.
5. Open the browser approval URL.
6. Complete Google login if prompted.
7. Approve the CLI login request.
8. Return to Postman and call `POST /api/cli/auth/exchange` again.
9. Copy the returned bearer token into `Authorization: Bearer {{access_token}}`.
10. Call `GET /api/cli/auth/me`.
11. Call `POST /api/cli/auth/logout`.
12. Confirm `GET /api/cli/auth/me` now returns `401`.

## Notes
- CLI API authentication is bearer-token based only.
- Browser session is used only during the human approval step.
- No bearer token is returned in any browser redirect URL.
- Device codes are one-time exchange values.
