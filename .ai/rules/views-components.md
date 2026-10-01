---
paths:
  - 'app/Actions/Fortify/CreateNewUser.php,app/Models/TechnicianVerification.php,resources/views/components/technician-registration-form.blade.php'
---

# Views Components

## Require shop names for walk-in service
Technician service_type values walkin and both require shop_name and persist it on technician_verifications. Home-only registrations keep shop_name null, and the UI must disable the hidden shop input when home is selected.
