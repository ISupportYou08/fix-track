---
paths:
  - 'resources/views/pages/auth/register.blade.php,resources/js/technician-registration.js'
---

# Js

## Keep registration Alpine state in JavaScript
Initialize the registration UI with technicianRegistration(role, serviceType) from the JS module. Do not place the multiline state object in a single-quoted x-data attribute because @js string values can close the attribute and leak code into the page.
