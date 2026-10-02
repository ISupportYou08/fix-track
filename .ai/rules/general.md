---
paths:
  - vercel.json
---

# General

## Preserve numeric Laravel production settings
Vercel production must set SESSION_LIFETIME to a positive minute value and BCRYPT_ROUNDS to a valid bcrypt cost (currently 120 and 12). Empty or zero values expire session cookies immediately and make password rehashing fail during login.

## Do not run PHP in the Vercel build command
Vercel executes buildCommand before vercel-php installs the PHP runtime, so `php artisan` fails with `php: command not found`. Run production migrations through the CRON_SECRET-protected `/internal/cron/migrate-database` runtime endpoint using a temporary one-time cron schedule, then remove that schedule after verification.
