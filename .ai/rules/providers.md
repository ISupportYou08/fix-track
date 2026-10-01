---
paths:
  - app/Providers/FortifyServiceProvider.php
---

# Providers

## Keep admin authentication isolated
Administrator and superadmin accounts authenticate only through the named admin.login routes at /admin/login. Public password, Google, and passkey login paths must reject admin roles; keep both portals on Fortify's credential validation, throttling, session regeneration, and two-factor pipeline.

## Gate public sign-in by account status
Public password, Google, and passkey sign-in must use User::mayUsePublicLogin. Suspended and banned accounts are denied; technician email-pending, review-pending, and rejected accounts may sign in to finish onboarding or resubmit. Keep admin portal authentication separate.
