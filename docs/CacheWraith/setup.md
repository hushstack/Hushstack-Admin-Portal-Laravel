# CacheWraith Setup

## Environment Variable

Add this to `.env`:

```env
CACHEWRAITH_AGENT_TOKEN=replace-with-a-long-random-secret
```

Recommended token rules:

- Use at least 32-64 random characters.
- Do not commit the token.
- Do not send it in query strings.
- Only send it in the `Authorization` header.

Example:

```env
CACHEWRAITH_AGENT_TOKEN=V3R3yLongRandomTokenForCacheWraithAgentOnly
```

After updating `.env`, clear cached config if needed:

```bash
php artisan config:clear
```

## Database Migration

Run migrations:

```bash
php artisan migrate
```

The alert data is stored in the `alerts` table.

Important fields:

- `uuid`
- `source`
- `server_name`
- `device_name`
- `type`
- `severity`
- `status`
- `title`
- `message`
- `source_ip`
- `user_name`
- `auth_method`
- `website_url`
- `service_name`
- `path`
- `fingerprint`
- `occurrence_count`
- `first_seen_at`
- `last_seen_at`
- `acknowledged_at`
- `acknowledged_by`
- `resolved_at`
- `resolved_by`
- `metadata`

Supported severities:

```text
info
warning
critical
```

Supported statuses:

```text
open
acknowledged
resolved
```
