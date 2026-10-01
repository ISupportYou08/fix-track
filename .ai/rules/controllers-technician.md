---
paths:
  - app/Http/Controllers/Technician/ApplicationResubmissionController.php
---

# Controllers Technician

## Resubmission mirrors technician application data
Rejected technicians may update registration identity and service fields and optionally replace valid_id or credentials. Keep verified email/password outside this workflow, store replacements on the private local disk, delete superseded files only after the database commit, and mirror service_area to verification.address.
