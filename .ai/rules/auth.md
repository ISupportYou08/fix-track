---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## Create technician accounts only after email confirmation
Technician signup stores an encrypted pending registration and private documents first. The six-digit email code must be confirmed before creating a users row, technician verification, or public avatar. Changing the pending email replaces the code; approved accounts are separate from the administrator review queue.
