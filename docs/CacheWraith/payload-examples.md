# CacheWraith Payload Examples

## SSH Brute Force

```bash
curl -X POST "https://your-domain.example/api/alerts" \
  -H "Authorization: Bearer ${CACHEWRAITH_AGENT_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "server_name": "vultr",
    "device_name": "vultr",
    "type": "ssh_bruteforce",
    "severity": "critical",
    "title": "SSH BRUTE FORCE DETECTED",
    "message": "Failed SSH attempts exceeded threshold",
    "source_ip": "1.2.3.4",
    "user_name": "root",
    "auth_method": "password",
    "fingerprint": "ssh_bruteforce:vultr:1.2.3.4",
    "metadata": {
      "failed_attempts": 10,
      "window": "5 minutes"
    },
    "occurred_at": "2026-04-26T21:40:00+07:00"
  }'
```

## Website Down

```bash
curl -X POST "https://your-domain.example/api/alerts" \
  -H "Authorization: Bearer ${CACHEWRAITH_AGENT_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "server_name": "edge-01",
    "type": "website_down",
    "severity": "critical",
    "title": "Website is unreachable",
    "message": "Health check returned connection timeout",
    "website_url": "https://example.com",
    "fingerprint": "website_down:edge-01:https://example.com",
    "metadata": {
      "timeout_seconds": 10,
      "check": "https"
    }
  }'
```

## Service Down Without Fingerprint

```bash
curl -X POST "https://your-domain.example/api/alerts" \
  -H "Authorization: Bearer ${CACHEWRAITH_AGENT_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "server_name": "vultr",
    "type": "service_down",
    "severity": "warning",
    "title": "Nginx service is down",
    "service_name": "nginx",
    "metadata": {
      "exit_code": 1
    }
  }'
```

Laravel will generate this fingerprint:

```text
service_down:vultr:nginx
```

## Suggested Fingerprint Patterns

```text
ssh_bruteforce:{server_name}:{source_ip}
ssh_success_after_failures:{server_name}:{source_ip}:{user_name}
website_down:{server_name}:{website_url}
service_down:{server_name}:{service_name}
high_cpu:{server_name}
high_ram:{server_name}
disk_full:{server_name}:{path}
nginx_error_spike:{server_name}:{website_url}
```

## Agent Notes

The CacheWraith C++ agent should:

- Send JSON only.
- Use `Authorization: Bearer <token>`.
- Reuse stable fingerprints for repeat events.
- Avoid putting secrets in `message`, `metadata`, `path`, or URLs.
- Prefer ISO 8601 timestamps for `occurred_at`.
- Keep `metadata` compact.
