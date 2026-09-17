---
paths:
  - 'app/Models/{Booking,Payment}.php,app/Livewire/{Customer,Technician,SuperAdmin}/**,resources/views/livewire/{customer,technician,super-admin}/**,database/migrations/**,tests/Feature/**'
---

# Feature

## Payments are cash only and belong through bookings
Every payment method must be `cash`; enforce it in the model and database. A completed assigned booking creates one pending cash payment. Technician ownership is derived through payment.booking.assigned_technician_id so the earnings account reflects the job without duplicating technician_id on payments.
