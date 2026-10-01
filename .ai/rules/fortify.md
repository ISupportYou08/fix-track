---
paths:
  - app/Actions/Fortify/CreateNewUser.php
---

# Fortify

## Customer registration identity fields
The customer form uses first_name, optional middle_name, surname, phone, and address. Validate the required fields, compose users.name from the name parts for existing displays, and persist the parts plus contact details. Keep the optional profile photo upload. The customer form is a fixed viewport component outside .auth-stack so the role selector animation cannot clip it.
