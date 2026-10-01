---
paths:
  - 'app/Http/Controllers/Auth/**,app/Actions/Auth/**,app/Http/Requests/*Google*,app/Http/Middleware/EnsurePendingGoogleRegistration.php,resources/views/{pages/auth/register,components/*registration-form}.blade.php'
---

# Auth Registercomponents

## Complete new Google identities before account creation
For an unknown verified Google identity, keep only its profile claims in a 15-minute server session, then require Customer or Technician selection and the matching profile fields before creating users. Google supplies the locked verified email, so skip password and email OTP. Customer accounts activate immediately; technician accounts create private evidence and remain review_pending for staff approval. Never create an incomplete users row when OAuth begins.
