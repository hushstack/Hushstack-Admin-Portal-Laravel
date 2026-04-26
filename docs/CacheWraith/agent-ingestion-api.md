# CacheWraith Agent Ingestion API

## Endpoint

```http
POST /api/alerts
```

This endpoint is for the CacheWraith C++ agent only.

It is protected by:

- `cachewraith.agent`
- `throttle:cachewraith-alerts`

## Authentication

The ingestion endpoint does not use Sanctum user authentication.

The agent must send:

```http
Authorization: Bearer <CACHEWRAITH_AGENT_TOKEN>
Content-Type: application/json
Accept: application/json
```

Missing or invalid token returns:

```json
{
  "status_code": 401,
  "status": "error",
  "message": "Unauthenticated.",
  "errors": null
}
```

## Payload

Example:

```json
{
  "server_name": "vultr",
  "device_name": "vultr",
  "type": "ssh_bruteforce",
  "severity": "critical",
  "title": "SSH BRUTE FORCE DETECTED",
  "message": "Failed SSH attempts exceeded threshold",
  "source_ip": "1.2.3.4",
  "user_name": "root",
  "auth_method": "password",
  "website_url": null,
  "service_name": null,
  "path": null,
  "fingerprint": "ssh_bruteforce:vultr:1.2.3.4",
  "metadata": {
    "failed_attempts": 10,
    "window": "5 minutes"
  },
  "occurred_at": "2026-04-26T21:40:00+07:00"
}
```

## Validation Rules

| Field | Rule |
| --- | --- |
| `server_name` | required string max 255 |
| `device_name` | nullable string max 255 |
| `type` | required string max 100 |
| `severity` | required, one of `info`, `warning`, `critical` |
| `title` | required string max 255 |
| `message` | nullable string max 10000 |
| `source_ip` | nullable IP address max 45 |
| `user_name` | nullable string max 150 |
| `auth_method` | nullable string max 100 |
| `website_url` | nullable URL max 2048 |
| `service_name` | nullable string max 150 |
| `path` | nullable string max 2048 |
| `fingerprint` | nullable string max 255 |
| `metadata` | nullable object/array |
| `occurred_at` | nullable date |

## Deduplication

If `fingerprint` is provided, Laravel uses it directly.

If `fingerprint` is missing, Laravel generates one from:

```text
type + server_name + source_ip/website_url/service_name/path
```

If an open alert exists with the same fingerprint:

- `occurrence_count` is incremented.
- `last_seen_at` is updated.
- latest useful fields are updated.
- `first_seen_at` stays unchanged.
- response status is `200`.

If no open alert exists:

- a new alert is created.
- `occurrence_count` starts at `1`.
- `first_seen_at` and `last_seen_at` use `occurred_at` or `now()`.
- status is `open`.
- response status is `201`.

Resolved and acknowledged alerts are not deduplicated as open alerts.

## Success Response: New Alert

```json
{
  "status_code": 201,
  "status": "ok",
  "message": "Alert created.",
  "data": {
    "id": 1,
    "source": "cachewraith",
    "server_name": "vultr",
    "type": "ssh_bruteforce",
    "severity": "critical",
    "status": "open",
    "fingerprint": "ssh_bruteforce:vultr:1.2.3.4",
    "occurrence_count": 1
  }
}
```

## Success Response: Duplicate Open Alert

```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Alert occurrence updated.",
  "data": {
    "id": 1,
    "status": "open",
    "fingerprint": "ssh_bruteforce:vultr:1.2.3.4",
    "occurrence_count": 2
  }
}
```
