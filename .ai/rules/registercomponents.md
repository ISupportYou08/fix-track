---
paths:
  - 'app/Actions/Fortify/CreateNewUser.php,app/Models/{User,TechnicianVerification}.php,resources/views/{pages/auth/register,components/technician-registration-form}.blade.php'
---

# Registercomponents

## Technician registration data contract
Technician sign-up captures first_name, middle_name, surname and composes users.name for existing displays. Mirror the submitted phone to users and technician_verifications. Store service_type on technician_verifications using only home, walkin, or both, and source capability choices from the active service catalog.
