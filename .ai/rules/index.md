# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/Auth/**,app/Actions/Auth/**,app/Http/Requests/*Google*,app/Http/Middleware/EnsurePendingGoogleRegistration.php,resources/views/{pages/auth/register,components/*registration-form}.blade.php | .ai/rules/auth-registercomponents.md |
| app/Http/Controllers/Auth/** | .ai/rules/auth.md |
| resources/views/pages/auth/register.blade.php,resources/views/components/technician-registration-form.blade.php | .ai/rules/components.md |
| app/Http/Controllers/Technician/ApplicationResubmissionController.php | .ai/rules/controllers-technician.md |
| routes/web.php,app/Http/Middleware/EnsureUserHasRole.php,app/Models/*.php,app/Livewire/{Customer,Technician,SuperAdmin}/** | .ai/rules/customer-technician-super-admin.md |
| routes/web.php,tests/Feature/Auth/** | .ai/rules/feature-auth.md |
| app/Models/{Booking,Payment}.php,app/Livewire/{Customer,Technician,SuperAdmin}/**,resources/views/livewire/{customer,technician,super-admin}/**,database/migrations/**,tests/Feature/** | .ai/rules/feature.md |
| app/Actions/Fortify/CreateNewUser.php | .ai/rules/fortify.md |
| vercel.json | .ai/rules/general.md |
| resources/views/pages/auth/register.blade.php,resources/js/technician-registration.js | .ai/rules/js.md |
| routes/web.php,app/Models/User.php,app/Providers/FortifyServiceProvider.php,app/Http/Middleware/EnsureUserHasRole.php,resources/views/{pages/auth,livewire/super-admin,components,layouts/app}/**,database/migrations/**,tests/Feature/** | .ai/rules/migrations-feature.md |
| app/Actions/Fortify/CreateNewUser.php,app/Models/TechnicianDocument.php,resources/views/components/technician-registration-form.blade.php,database/migrations/** | .ai/rules/migrations.md |
| app/Providers/FortifyServiceProvider.php | .ai/rules/providers.md |
| app/Actions/Fortify/CreateNewUser.php,app/Models/{User,TechnicianVerification}.php,resources/views/{pages/auth/register,components/technician-registration-form}.blade.php | .ai/rules/registercomponents.md |
| app/Livewire/SuperAdmin/**,resources/views/components/super-admin/**,tests/Feature/** | .ai/rules/super-admin-feature.md |
| app/Livewire/SuperAdmin/** | .ai/rules/super-admin.md |
| app/Livewire/SuperAdmin/**,resources/views/{livewire/super-admin,components}/**,tests/Feature/** | .ai/rules/super-admincomponents-feature.md |
| app/{Actions/WalkIns/**,Models/WalkInEntry.php,Livewire/Customer/ModulePage.php,Livewire/Technician/ModulePage.php} | .ai/rules/technician.md |
| app/Actions/Fortify/CreateNewUser.php,app/Models/TechnicianVerification.php,resources/views/components/technician-registration-form.blade.php | .ai/rules/views-components.md |
