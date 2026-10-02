---
paths:
  - 'app/Actions/Bookings/AnalyzeServiceItemImage.php,app/Livewire/Customer/ModulePage.php,app/Models/BookingItemAnalysis.php,app/Http/Controllers/BookingItemImageController.php,resources/views/livewire/{customer,technician}/module-page.blade.php,tests/Feature/AiAssistedBookingTest.php'
---

# Customertechnician Feature

## Require three AI item views
The customer item scanner requires exactly three camera or uploaded images. Keep the Analyze images action hidden until all three files exist. Send the three Cloudflare requests concurrently with a provider timeout below PHP's request limit, combine valid catalog-bound results, save all confirmed images privately, and expose them only through the authorized booking item image route.
