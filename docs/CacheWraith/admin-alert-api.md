# CacheWraith Admin Alert API

Admin alert endpoints require:

```text
auth:sanctum
super_admin
matching alert permission
```

Only users with role slug `super-admin` can access these endpoints.

Super Admin users pass permission checks automatically, but the permission records still exist in the database for consistency with the project permission system.

Alert permissions:

| Permission | Used by |
| --- | --- |
| `alerts.view` | list and view alert detail |
| `alerts.acknowledge` | acknowledge alert |
| `alerts.resolve` | resolve alert |
| `alerts.reopen` | reopen alert |
| `alerts.delete` | delete alert |

## List Alerts

```http
GET /api/admin/alerts
```

Permission:

```text
alerts.view
```

Filters:

| Query param | Description |
| --- | --- |
| `severity` | `info`, `warning`, `critical` |
| `status` | `open`, `acknowledged`, `resolved` |
| `type` | exact alert type |
| `server_name` | exact server name |
| `source_ip` | exact IP |
| `date_from` | filter by `last_seen_at` date |
| `date_to` | filter by `last_seen_at` date |
| `search` | searches title, message, server name, type, fingerprint |
| `per_page` | 1-100 |

Default sort:

```text
last_seen_at desc
```

Example:

```bash
curl -X GET "https://your-domain.example/api/admin/alerts?severity=critical&status=open&per_page=20" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```

## View Alert

```http
GET /api/admin/alerts/{alert}
```

Permission:

```text
alerts.view
```

Example:

```bash
curl -X GET "https://your-domain.example/api/admin/alerts/1" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```

## Acknowledge Alert

```http
POST /api/admin/alerts/{alert}/acknowledge
```

Permission:

```text
alerts.acknowledge
```

Effect:

- status becomes `acknowledged`
- `acknowledged_at` is set
- `acknowledged_by` is set to current super-admin user

Example:

```bash
curl -X POST "https://your-domain.example/api/admin/alerts/1/acknowledge" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```

## Resolve Alert

```http
POST /api/admin/alerts/{alert}/resolve
```

Permission:

```text
alerts.resolve
```

Effect:

- status becomes `resolved`
- `resolved_at` is set
- `resolved_by` is set to current super-admin user

Example:

```bash
curl -X POST "https://your-domain.example/api/admin/alerts/1/resolve" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```

## Reopen Alert

```http
POST /api/admin/alerts/{alert}/reopen
```

Permission:

```text
alerts.reopen
```

Effect:

- status becomes `open`
- acknowledged fields are cleared
- resolved fields are cleared

Example:

```bash
curl -X POST "https://your-domain.example/api/admin/alerts/1/reopen" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```

## Delete Alert

```http
DELETE /api/admin/alerts/{alert}
```

Permission:

```text
alerts.delete
```

Example:

```bash
curl -X DELETE "https://your-domain.example/api/admin/alerts/1" \
  -H "Authorization: Bearer ${ADMIN_SANCTUM_TOKEN}" \
  -H "Accept: application/json"
```
