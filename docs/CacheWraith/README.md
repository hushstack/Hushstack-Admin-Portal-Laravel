# CacheWraith Alert API

CacheWraith is the C++ monitoring agent that posts server, SSH, Nginx, website, service, CPU, RAM, and disk alerts into this Laravel admin portal.

This docs folder is split by topic:

- [Setup](setup.md)
- [Agent Ingestion API](agent-ingestion-api.md)
- [Admin Alert API](admin-alert-api.md)
- [Payload Examples](payload-examples.md)
- [Implementation Notes](implementation-notes.md)
- [Testing](testing.md)

## Quick Summary

Agent endpoint:

```http
POST /api/alerts
```

Agent auth:

```http
Authorization: Bearer <CACHEWRAITH_AGENT_TOKEN>
```

Admin endpoints require:

```text
auth:sanctum
super_admin
```

Alert severities:

```text
info
warning
critical
```

Alert statuses:

```text
open
acknowledged
resolved
```
