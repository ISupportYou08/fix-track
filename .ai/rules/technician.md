---
paths:
  - 'app/{Actions/WalkIns/**,Models/WalkInEntry.php,Livewire/Customer/ModulePage.php,Livewire/Technician/ModulePage.php}'
---

# Technician

## Walk-In tickets use one transactional workflow
Create Walk-In tickets through CreateWalkInEntry so the global maximum of 3 active tickets is enforced under a database lock and queue numbers remain unique. Change ticket states through WalkInEntry::transitionTo so history, cancellation attribution, timestamps, and location shutdown stay consistent. Never delete cancelled or completed tickets; payment_method is always cash, and only the owning customer plus the assigned technician/admin may access live coordinates.
