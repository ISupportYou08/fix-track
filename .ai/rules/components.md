---
paths:
  - 'resources/views/pages/auth/register.blade.php,resources/views/components/technician-registration-form.blade.php'
---

# Components

## Keep technician form outside auth animation
Render the fixed full-viewport technician form outside `.auth-stack`. The auth fade animation leaves a transform on that wrapper, which makes fixed descendants use the 360px auth card as their containing block and clips the split layout.
