---
paths:
  - vercel.json
---

# General

## Preserve numeric Laravel production settings
Vercel production must set SESSION_LIFETIME to a positive minute value and BCRYPT_ROUNDS to a valid bcrypt cost (currently 120 and 12). Empty or zero values expire session cookies immediately and make password rehashing fail during login.
