---
paths:
  - 'app/Livewire/SuperAdmin/**'
---

# Super Admin

## Keep technician approval behind email confirmation
The Technician admin page has separate needs-verification and approved-account views. An administrator may approve a technician only when users.email_verified_at is set, including for legacy email-pending records. Approved verification status activates the technician account.

## Keep technician penalties separate from verification
After a technician verification is approved, keep its status approved. Suspend, ban, and restore the user's account_status through the Technician admin view with a required message and confirmation; keep the message in audit logs and notify the technician. Return merely assigned bookings to matching when access is restricted, and leave jobs already underway for manual follow-up.

## Lock bookings before technicians in account actions
Admin booking assignment locks the booking before the technician. Account suspension and ban must lock assigned bookings before the technician user row as well, so simultaneous assignment and restriction do not acquire the same locks in opposite order.

## Timed verified technician suspension
Verified technician Suspend requires a 1–365 day duration and a penalty message. Persist the expiry in users.suspended_until; Ban and Restore clear it. The scheduled technicians:expire-suspensions command restores expired suspensions, with authentication as a fallback when the scheduler has not run. Keep verification status approved throughout account penalties.
