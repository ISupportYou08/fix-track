---
paths:
  - '{app/Livewire/Customer/ModulePage.php,resources/views/livewire/customer/module-page.blade.php,resources/js/ai-item-camera.js,tests/Feature/AiAssistedBookingTest.php}'
---

# Js Feature

## Restart AI item photos during retakes
Each AI item preview must offer a retake action that removes that view, opens the camera, and hides Analyze until exactly three views exist again. When identification is unsupported, Retake all photos must clear the set and restart capture at photo 1.

## Keep AI photo views ordered
The three AI booking images are ordered Front, Side, Back. A single-view retake must replace its original index so the remaining previews do not change labels; Analyze remains hidden until all three slots are filled.
