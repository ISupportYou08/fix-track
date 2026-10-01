---
paths:
  - 'app/Actions/Fortify/CreateNewUser.php,app/Models/TechnicianDocument.php,resources/views/components/technician-registration-form.blade.php,database/migrations/**'
---

# Migrations

## Persist technician application evidence privately
Technician registration requires a valid ID image (JPG, PNG, or WEBP) and credentials document (PDF, DOC, or DOCX), each at most 5 MB. Keep uploads on the local/private disk until email confirmation; then create technician_documents.file_path records. Mirror service_area into technician_verifications.address because approved walk-in discovery reads address.
