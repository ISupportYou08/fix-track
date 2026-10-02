---
paths:
  - '{app/Livewire/Customer/ModulePage.php,resources/views/livewire/customer/module-page.blade.php,tests/Feature/AiAssistedBookingTest.php}'
---

# Customer Feature

## Prefill reviewable possible AI matches
When AI returns a low-confidence match that still maps to an active service catalog record, offer Book this possible item and open manual home-service booking with its category and service selected for customer review. Never offer this shortcut when the service code is missing or outside the live catalog, and do not persist a low-confidence result as a confirmed analysis.
