---
paths:
  - 'routes/web.php,tests/Feature/Auth/**'
---

# Feature Auth

## Keep operations portal roots usable
The `/staff` and `/admin` root URLs are portal entry routes. Guests redirect to the matching sign-in page, authenticated Staff or Administrator accounts redirect to their matching dashboard, and accounts from other roles receive 403.
