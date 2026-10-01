---
paths:
  - 'routes/web.php,app/Models/User.php,app/Providers/FortifyServiceProvider.php,app/Http/Middleware/EnsureUserHasRole.php,resources/views/{pages/auth,livewire/super-admin,components,layouts/app}/**,database/migrations/**,tests/Feature/**'
---

# Migrations Feature

## Separate staff and administrator portals
The former regular `admin` role is now `staff` and uses named `staff.*` routes under `/staff`. The `superadmin` database role is the administrator identity and alone uses `admin.*` routes under `/admin`. Both may use the shared operations workspace; public customer/technician authentication must reject both roles.
