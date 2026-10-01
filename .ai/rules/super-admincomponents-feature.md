---
paths:
  - 'app/Livewire/SuperAdmin/**,resources/views/{livewire/super-admin,components}/**,tests/Feature/**'
---

# Super Admincomponents Feature

## Separate administrator controls from staff operations
The superadmin role is the Administrator and may access every operations module, including all-user management, staff account creation, audit logs, system health, and platform settings. Staff keeps technician review, bookings, dispatch, walk-ins, payments, reviews, reports, support, and service catalog; do not expose administrator-only module links or actions to Staff.
