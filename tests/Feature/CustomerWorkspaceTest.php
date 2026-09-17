<?php

use App\Livewire\Customer\ModulePage;
use App\Models\PlatformSetting;
use App\Models\Review;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\WalkInEntry;
use App\Notifications\BookingStatusChanged;
use App\Support\QueueTicketPdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function customerBooking(int $customerId, string $status = 'pending', ?int $technicianId = null): int
{
    return DB::table('bookings')->insertGetId([
        'user_id' => $customerId,
        'assigned_technician_id' => $technicianId,
        'reference' => 'CUST-'.fake()->unique()->numerify('#####'),
        'customer_name' => 'FixTrack Customer',
        'customer_phone' => '09170000000',
        'service_type' => 'plumbing',
        'booking_type' => 'quick',
        'status' => $status,
        'address' => 'Quezon City',
        'description' => 'Repair kitchen plumbing.',
        'scheduled_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('customers are redirected to their workspace and see customer modules', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertRedirect(route('customer.module'));

    $this->actingAs($customer)
        ->get(route('customer.module'))
        ->assertOk()
        ->assertSee('Book a Service')
        ->assertSee('My Bookings')
        ->assertSee('Quotations')
        ->assertSee('Ratings &amp; Reviews', false)
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation')
        ->assertDontSee('System Health');
});

test('customer dashboard shows only the five most-booked services', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $bookingCustomer = User::factory()->create(['role' => 'customer']);
    $services = [
        ['code' => 'ranked-service-1', 'name' => 'Ranked Service One', 'bookings' => 6],
        ['code' => 'ranked-service-2', 'name' => 'Ranked Service Two', 'bookings' => 5],
        ['code' => 'ranked-service-3', 'name' => 'Ranked Service Three', 'bookings' => 4],
        ['code' => 'ranked-service-4', 'name' => 'Ranked Service Four', 'bookings' => 3],
        ['code' => 'ranked-service-5', 'name' => 'Ranked Service Five', 'bookings' => 2],
        ['code' => 'ranked-service-6', 'name' => 'Ranked Service Six', 'bookings' => 1],
    ];

    foreach ($services as $service) {
        ServiceCatalog::create([
            'code' => $service['code'],
            'name' => $service['name'],
            'category' => 'Ranked services',
            'is_active' => true,
            'description' => 'A service used to verify popularity ranking.',
        ]);

        foreach (range(1, $service['bookings']) as $bookingNumber) {
            DB::table('bookings')->insert([
                'user_id' => $bookingCustomer->id,
                'assigned_technician_id' => null,
                'reference' => "RANK-{$service['bookings']}-{$bookingNumber}",
                'customer_name' => $bookingCustomer->name,
                'customer_phone' => '09170000000',
                'service_type' => $service['code'],
                'booking_type' => 'quick',
                'status' => 'completed',
                'address' => 'Quezon City',
                'description' => 'Popularity ranking fixture.',
                'scheduled_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    $this->actingAs($customer)
        ->get(route('customer.module'))
        ->assertOk()
        ->assertSee('Top 5 Popular Services')
        ->assertSeeInOrder([
            'Ranked Service One',
            'Ranked Service Two',
            'Ranked Service Three',
            'Ranked Service Four',
            'Ranked Service Five',
        ])
        ->assertDontSee('Ranked Service Six');
});

test('customer dashboard keeps the latest completed booking visible with a completed progress step', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $bookingId = customerBooking($customer->id, 'completed');
    $reference = DB::table('bookings')->where('id', $bookingId)->value('reference');

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->assertSee($reference)
        ->assertSee('This service has been marked completed.')
        ->assertSeeHtml('data-service-progress-step="completed" data-complete="true"')
        ->assertDontSee('No active service');
});

test('customer dashboard prioritizes an active booking over a completed booking', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    customerBooking($customer->id, 'in_progress');
    customerBooking($customer->id, 'completed');

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->assertSee('Follow the next step in your home service.')
        ->assertDontSee('This service has been marked completed.')
        ->assertSeeHtml('data-service-progress-step="completed" data-complete="false"');
});

test('customer module routes expose the migrated customer content', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    foreach ([
        'book-service' => ['Book a Service', 'Request a technician'],
        'my-bookings' => ['My Bookings', 'Booking history'],
        'quotations' => ['Quotations', 'No quotations to review'],
        'payments' => ['Payments', 'Payment history'],
        'ratings-reviews' => ['Ratings & Reviews', 'Your reviews'],
        'notifications' => ['Notifications', 'Latest updates'],
        'support' => ['Support & Help', 'Contact support'],
        'settings' => ['Settings', 'Account preferences'],
    ] as $slug => [$label, $content]) {
        $this->actingAs($customer)
            ->get(route('customer.module', ['module' => $slug]))
            ->assertOk()
            ->assertSee($label)
            ->assertSee($content);
    }
});

test('customers can review their payment summary and open only their payment details', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $bookingId = customerBooking($customer->id, 'completed');
    $otherBookingId = customerBooking($otherCustomer->id, 'completed');

    $paymentId = DB::table('payments')->insertGetId([
        'booking_id' => $bookingId,
        'amount' => 1500,
        'status' => 'paid',
        'method' => 'cash',
        'transaction_ref' => 'FT-PAY-1001',
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $otherPaymentId = DB::table('payments')->insertGetId([
        'booking_id' => $otherBookingId,
        'amount' => 900,
        'status' => 'paid',
        'method' => 'cash',
        'transaction_ref' => 'FT-PAY-1002',
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'payments'])
        ->assertSee('Total paid')
        ->assertSee('₱1,500.00')
        ->assertSee('Cash')
        ->assertSee('View details')
        ->call('openPayment', $paymentId)
        ->assertSet('showPaymentDetails', true)
        ->assertSee('FT-PAY-1001');

    expect(fn () => (new ModulePage)->openPayment($otherPaymentId))
        ->toThrow(HttpException::class);
});

test('customers see an unavailable state instead of a broken walk-in queue form', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    PlatformSetting::create([
        'key' => 'walk_in_queue_enabled',
        'group' => 'booking',
        'label' => 'Walk-in queue',
        'value' => 'false',
        'type' => 'boolean',
    ]);

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'walk-in-queue'])
        ->assertSee('Walk-in queue is currently unavailable')
        ->assertDontSee('Check in for an in-person service visit.');
});

test('customers can create a quick booking with the source validation rules', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    ServiceCatalog::create([
        'code' => 'aircon',
        'name' => 'Aircon cleaning',
        'category' => 'Cooling',
        'base_price' => 1800,
        'is_active' => true,
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('customerName', 'Maria Santos')
        ->set('customerPhone', '09171234567')
        ->set('serviceType', 'aircon')
        ->set('bookingType', 'quick')
        ->set('address', 'Makati City')
        ->set('description', 'The unit needs cleaning.')
        ->call('createBooking')
        ->assertSet('moduleSlug', 'my-bookings')
        ->assertDispatched('toast-show', function (string $event, array $params): bool {
            return data_get($params, 'dataset.variant') === 'success';
        });

    expect(DB::table('bookings')->where('user_id', $customer->id)->first())
        ->service_type->toBe('aircon')
        ->status->toBe('matching')
        ->booking_type->toBe('quick')
        ->address->toBe('Makati City')
        ->latitude->toBeNull()
        ->longitude->toBeNull();
});

test('scheduled home service blocks past Manila times and exposes a future picker minimum', function () {
    $this->travelTo(Carbon::parse('2026-09-17 10:15:00', 'Asia/Manila'));
    $customer = User::factory()->create(['role' => 'customer']);
    ServiceCatalog::create([
        'code' => 'aircon',
        'name' => 'Aircon cleaning',
        'category' => 'Cooling',
        'base_price' => 1800,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('bookingFlow', 'manual')
        ->set('manualServiceMode', 'home-service')
        ->set('bookingStep', 3)
        ->assertSee('min="2026-09-17T10:16"', false)
        ->set('customerPhone', '9171234567')
        ->set('serviceType', 'aircon')
        ->set('bookingType', 'scheduled')
        ->set('address', 'Sampaloc, Manila')
        ->set('scheduledAt', '2026-09-17T09:30')
        ->call('createBooking')
        ->assertHasErrors(['scheduledAt'])
        ->assertSee('Choose a future date and time. Past schedules are not allowed.');

    expect(DB::table('bookings')->where('user_id', $customer->id)->exists())->toBeFalse();

    $component
        ->set('scheduledAt', '2026-09-17T10:30')
        ->call('createBooking')
        ->assertHasNoErrors('scheduledAt');

    expect(DB::table('bookings')->where('user_id', $customer->id)->value('scheduled_at'))
        ->toBe('2026-09-17 10:30:00');
});

test('request technician initially shows only quick and manual booking choices', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->assertSee('Quick booking')
        ->assertSee('Manual booking')
        ->assertDontSee('Schedule a visit')
        ->assertDontSee('Choose a service category')
        ->call('openBookingFlow', 'quick')
        ->assertSet('showBookingFlow', true)
        ->assertSet('bookingFlow', 'quick')
        ->assertSet('bookingStep', 1)
        ->assertSee('Choose a service category');
});

test('quick booking advances from service details to address and number', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'quick')
        ->call('chooseServiceCategory', 'Electronics')
        ->call('chooseServiceType', 'electronics-battery')
        ->set('description', 'The phone only charges at a certain angle.')
        ->call('nextBookingStep')
        ->assertHasNoErrors()
        ->assertSet('bookingStep', 2)
        ->assertSee('Set your address and number')
        ->assertSee('Your name')
        ->assertSet('customerName', $customer->name)
        ->assertSee('wire:model="customerName"', false)
        ->assertDontSee('Choose a service category');
});

test('manual booking asks for walk-in or home service before service details', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'manual')
        ->assertSet('bookingStep', 1)
        ->assertSee('Manual booking')
        ->assertDontSee('Booking progress')
        ->assertDontSee('Step 1 of')
        ->assertSee('Walk-in')
        ->assertSee('Home service')
        ->assertSee('wire:model.live="manualServiceMode"', false)
        ->assertSee('wire:key="booking-flow-manual-service-mode-step"', false)
        ->assertDontSee('Choose a service category')
        ->set('manualServiceMode', 'home-service')
        ->assertSee('Manual booking')
        ->call('nextBookingStep')
        ->assertHasNoErrors()
        ->assertSet('bookingStep', 2)
        ->assertSee('Home service booking')
        ->assertSee('Booking progress')
        ->assertSee('Step 2 of 3')
        ->assertSee('data-booking-modal-header', false)
        ->assertSee('data-booking-progress-summary', false)
        ->assertSee('data-booking-step-count', false)
        ->assertSee('wire:key="booking-flow-service-details-step"', false)
        ->assertSee('Choose a service category')
        ->assertDontSee('Set your address and number')
        ->call('previousBookingStep')
        ->assertSet('bookingStep', 1)
        ->assertSee('Manual booking');
});

test('modal close controls stay visible above modal content', function () {
    $styles = file_get_contents(resource_path('css/app.css'));

    expect($styles)
        ->toContain('[data-flux-modal] [data-flux-modal-close]')
        ->toContain('z-index: 50;')
        ->toContain('dialog[data-modal]:not([data-flux-modal-overflow]) > :first-child')
        ->toContain('padding-inline-end: 4rem;');
});

test('manual walk-in booking joins the queue through the final contact step', function () {
    $this->seed();
    Http::fake([
        'https://nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Makati City, Metro Manila, Philippines',
                'lat' => '14.5547',
                'lon' => '121.0244',
            ],
        ]),
    ]);
    $customer = User::factory()->create(['role' => 'customer']);
    $walkInShop = User::query()->where('email', 'walkin-electronics-5@fixtrack.test')->firstOrFail();
    $this->travelTo(Carbon::parse('2026-09-17 03:24:00', 'Asia/Manila'));

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'manual')
        ->set('manualServiceMode', 'walk-in')
        ->assertSee('Manual booking')
        ->assertSee('min-h-28', false)
        ->assertSee('items-start gap-3', false)
        ->call('nextBookingStep')
        ->assertSet('bookingStep', 2)
        ->assertSee('Walk-in booking')
        ->assertSee('flex gap-2', false)
        ->assertSee('Choose a service category')
        ->assertSee('Repair and diagnostics for household devices and gadgets.')
        ->assertDontSee('Choose an exact service')
        ->assertDontSee('What needs attention?')
        ->call('chooseServiceCategory', 'Electronics')
        ->call('nextBookingStep')
        ->assertSet('bookingStep', 3)
        ->assertSee('Choose a walk-in shop')
        ->assertSee('Your location')
        ->assertSee('Sort shops by')
        ->assertDontSee('Search location')
        ->assertSee('5.0 rating')
        ->assertSee('Sample Technician Service Store')
        ->assertSee('Set your location to see the distance')
        ->assertSee('max-height: 20rem; overflow-y: auto;', false)
        ->assertSee('Next')
        ->call('nextBookingStep')
        ->assertHasErrors('walkInShopId')
        ->set('walkInShopAddress', 'Makati City')
        ->assertSee('Select your location')
        ->assertSee('Makati City, Metro Manila, Philippines')
        ->call('selectWalkInShopAddress', 0)
        ->assertSet('walkInShopAddress', 'Makati City, Metro Manila, Philippines')
        ->assertSet('walkInShopLatitude', 14.5547)
        ->assertSee('Showing shops in or near Makati City, Metro Manila, Philippines.')
        ->call('selectWalkInShop', $walkInShop->id)
        ->assertSet('selectedWalkInShopId', $walkInShop->id)
        ->call('nextBookingStep')
        ->assertSet('bookingStep', 4)
        ->assertSee('Your details')
        ->assertSee('Your name')
        ->assertSee('Email address')
        ->assertSet('walkInCustomerName', '')
        ->assertSet('walkInCustomerEmail', '')
        ->assertSet('walkInCustomerPhone', '')
        ->assertSee('wire:model="walkInCustomerName"', false)
        ->assertSee('wire:model="walkInCustomerEmail"', false)
        ->assertSee('wire:model="walkInCustomerPhone"', false)
        ->assertSee('Service request details')
        ->assertDontSee('Service address')
        ->set('walkInCustomerName', 'Walk-in Customer')
        ->set('walkInCustomerEmail', 'walk-in@example.test')
        ->set('walkInCustomerPhone', '9171234567')
        ->set('description', 'The battery drains too quickly.')
        ->call('submitBookingFlow')
        ->assertHasNoErrors()
        ->assertSet('bookingStep', 5)
        ->assertSet('showBookingFlow', true)
        ->assertSee('Your Queue Number Is')
        ->assertSee('Reference Number')
        ->assertSee('Shop Address')
        ->assertSee('Issued On')
        ->assertSee('September 17, 2026 3:24 AM')
        ->assertSee('Download');

    expect($component->html())->toMatch(
        '/data-walk-in-ticket-card[^>]*>.*'.preg_quote($walkInShop->name.' Service Store', '/').'.*Your walk-in queue ticket has been issued\./s',
    );

    expect(DB::table('walk_in_entries')->where('user_id', $customer->id)->first())
        ->service_type->toBe('Electronics')
        ->technician_id->toBe($walkInShop->id)
        ->customer_name->toBe('Walk-in Customer')
        ->customer_email->toBe('walk-in@example.test')
        ->reference->not->toBeNull()
        ->customer_phone->toBe('+639171234567')
        ->notes->toBe('The battery drains too quickly.');

    $ticket = WalkInEntry::query()
        ->with('technician.technicianVerification')
        ->where('user_id', $customer->id)
        ->firstOrFail();
    $pdf = app(QueueTicketPdf::class)->generate($ticket);

    expect($pdf)
        ->toStartWith('%PDF-1.4')
        ->toContain('FixTrack')
        ->toContain((string) $ticket->queue_number)
        ->toContain('September 17, 2026 3:24 AM')
        ->toEndWith("%%EOF\n");

    expect($ticket->checked_in_at?->equalTo(now()))->toBeTrue();

    $component
        ->call('downloadWalkInTicket')
        ->assertFileDownloaded('fixtrack-queue-'.Str::slug((string) $ticket->queue_number).'.pdf');
});

test('walk-in shop search filters matching city addresses when location lookup is unavailable', function () {
    $this->seed();
    Http::fake([
        'https://nominatim.openstreetmap.org/search*' => Http::failedConnection(),
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'manual')
        ->set('manualServiceMode', 'walk-in')
        ->call('nextBookingStep')
        ->call('chooseServiceCategory', 'Appliances')
        ->call('nextBookingStep')
        ->set('walkInShopAddress', 'Quezon City')
        ->call('searchWalkInShops')
        ->assertSee('Showing shops in or near Quezon City.')
        ->assertDontSee('Appliances Shop 1 Service Store')
        ->assertDontSee('Appliances Shop 2 Service Store')
        ->assertDontSee('Appliances Shop 3 Service Store')
        ->assertSee('Appliances Shop 4 Service Store')
        ->assertDontSee('Appliances Shop 5 Service Store');
});

test('walk-in booking uses the customer current location to sort shops by proximity', function () {
    $this->seed();
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'display_name' => 'Makati City, Metro Manila, Philippines',
            'address' => ['city' => 'Makati'],
        ]),
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'manual')
        ->set('manualServiceMode', 'walk-in')
        ->call('nextBookingStep')
        ->call('chooseServiceCategory', 'Appliances')
        ->call('nextBookingStep')
        ->assertSee('aria-label="Use my current location"', false)
        ->call('setWalkInShopLocation', 14.5547, 121.0244)
        ->assertSet('walkInShopLatitude', 14.5547)
        ->assertSet('walkInShopLongitude', 121.0244)
        ->assertSet('walkInShopSort', 'nearest')
        ->assertSet('walkInShopAddress', 'My location — Makati')
        ->assertSee('Showing shops in or near My location — Makati.')
        ->assertSee('km away');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://nominatim.openstreetmap.org/reverse?lat=14.5547&lon=121.0244&format=jsonv2&addressdetails=1&zoom=18');
});

test('walk-in booking keeps a visible current location when address lookup is unavailable', function () {
    $this->seed();
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::failedConnection(),
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'manual')
        ->set('manualServiceMode', 'walk-in')
        ->call('nextBookingStep')
        ->call('chooseServiceCategory', 'Appliances')
        ->call('nextBookingStep')
        ->call('setWalkInShopLocation', 14.60115, 121.01459)
        ->assertSet('walkInShopLatitude', 14.60115)
        ->assertSet('walkInShopLongitude', 121.01459)
        ->assertSet('walkInShopAddress', 'My location — Manila')
        ->assertSee('Showing shops in or near My location — Manila.')
        ->assertSee('Appliances Shop 1 Service Store')
        ->assertSee('Appliances Shop 2 Service Store')
        ->assertSee('Appliances Shop 3 Service Store')
        ->assertDontSee('Appliances Shop 4 Service Store')
        ->assertDontSee('Appliances Shop 5 Service Store');
});

test('customers can open details for their walk-in queue entry', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::query()->where('email', 'walkin-appliances-1@fixtrack.test')->firstOrFail();
    $entry = WalkInEntry::create([
        'user_id' => $customer->id,
        'technician_id' => $technician->id,
        'reference' => 'WALK-DETAIL-001',
        'queue_number' => 'W-DETAIL',
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '+639171234567',
        'service_type' => 'Appliances',
        'priority' => 'standard',
        'status' => 'waiting',
        'notes' => 'The refrigerator is not cooling properly.',
        'checked_in_at' => now(),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'walk-in-queue'])
        ->assertSee('wire:click="openWalkInEntry('.$entry->id.')"', false)
        ->call('openWalkInEntry', $entry->id)
        ->assertSet('selectedWalkInEntryId', $entry->id)
        ->assertSet('showWalkInEntryDetails', true)
        ->assertSee('Queue details')
        ->assertSee('W-DETAIL')
        ->assertSee('WALK-DETAIL-001')
        ->assertSee('Appliances Shop 1 Service Store')
        ->assertSee('The refrigerator is not cooling properly.')
        ->call('closeWalkInEntryDetails')
        ->assertSet('showWalkInEntryDetails', false);
});

test('customers see the restored default service categories in the booking form', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'quick')
        ->assertSee('Aircon cleaning')
        ->assertSee('Appliance repair')
        ->assertSee('Electrical repair')
        ->assertSee('Plumbing repair');
});

test('customers keep default service categories when the catalog table is empty', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->call('openMobileQuickBook')
        ->assertSee('Aircon cleaning')
        ->assertSee('Appliance repair')
        ->assertSee('Electrical repair')
        ->assertSee('Plumbing repair');
});

test('customers choose a service category before choosing the exact service', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'quick')
        ->assertSee('Choose a service category')
        ->assertSee('wire:model="serviceCategory"', false)
        ->assertSee('wire:model="serviceType"', false)
        ->assertSee('Back to categories')
        ->assertSee("wire:show=\"serviceCategory === '' || serviceCategory === 'Electronics'\"", false)
        ->assertSee('space-y-4 overflow-hidden', false)
        ->assertSee('max-h-56 overflow-y-auto overscroll-contain pr-2 sm:max-h-64', false)
        ->assertSee('max-w-3xl overflow-hidden pb-2!', false)
        ->assertDontSee('data-flux-modal-overflow')
        ->assertSee('These details will be shared with the technician so they can prepare.')
        ->assertSee('text-xs leading-4 text-zinc-500', false)
        ->assertSee('Aircon cleaning, Aircon diagnosis + 8 more services')
        ->assertSee('peer-checked:hidden', false)
        ->assertSee('mt-3 grid gap-2 sm:grid-cols-2', false)
        ->call('chooseServiceCategory', 'Electronics')
        ->assertSet('serviceCategory', 'Electronics')
        ->assertSee('space-y-3', false)
        ->assertSee('LCD / screen replacement')
        ->assertSee('Battery replacement')
        ->assertSee('Not sure — diagnose my device')
        ->call('chooseServiceType', 'electronics-lcd')
        ->assertSet('serviceType', 'electronics-lcd')
        ->assertSet('serviceCategory', 'Electronics');
});

test('booking flow requires the selected service to belong to its category', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'quick')
        ->set('serviceCategory', 'Electronics')
        ->set('serviceType', 'aircon-cleaning')
        ->set('description', 'The unit is not working correctly.')
        ->call('nextBookingStep')
        ->assertHasErrors('serviceType')
        ->assertSet('bookingStep', 1);
});

test('mobile customers use the same category then exact service flow', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->call('openMobileQuickBook')
        ->assertSee('Choose a service category')
        ->call('chooseServiceCategory', 'Electronics')
        ->assertSee('LCD / screen replacement')
        ->call('chooseServiceType', 'electronics-battery')
        ->assertSet('serviceType', 'electronics-battery');
});

test('booking uses the profile name, normalizes the phone, and stores the selected map location', function () {
    $customer = User::factory()->create(['role' => 'customer', 'name' => 'Maria Santos']);
    ServiceCatalog::create([
        'code' => 'aircon',
        'name' => 'Aircon cleaning',
        'category' => 'Cooling',
        'base_price' => 1800,
        'is_active' => true,
    ]);
    Http::fake([
        'https://nominatim.openstreetmap.org/*' => Http::response([
            ['display_name' => 'Makati City, Metro Manila, Philippines', 'lat' => '14.5547', 'lon' => '121.0244'],
        ]),
    ]);

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('customerName', 'A tampered name')
        ->set('mobileCountryCode', '+63')
        ->set('customerPhone', '9171234567')
        ->set('serviceType', 'aircon')
        ->set('address', 'Makati')
        ->call('searchAddress')
        ->call('selectAddress', 0)
        ->call('createBooking');

    $component
        ->assertSet('customerPhone', '+639171234567')
        ->call('openBooking', DB::table('bookings')->where('user_id', $customer->id)->value('id'))
        ->assertSee('Mobile number')
        ->assertSee('+639171234567');

    $booking = DB::table('bookings')->where('user_id', $customer->id)->first();

    expect($booking)
        ->customer_name->toBe('Maria Santos')
        ->customer_phone->toBe('+639171234567')
        ->address->toBe('Makati City, Metro Manila, Philippines');
    expect((float) $booking->latitude)->toBe(14.5547)
        ->and((float) $booking->longitude)->toBe(121.0244);
});

test('address search retries a provider failure before showing no results', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Http::fake([
        'https://nominatim.openstreetmap.org/*' => Http::response([], 503),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('address', 'Makati')
        ->call('searchAddress')
        ->assertSet('addressSuggestions', []);

    Http::assertSentCount(2);
});

test('address search falls back to an approximate area and preserves the customer address', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Sleep::fake();
    Http::fake([
        'https://nominatim.openstreetmap.org/search*' => Http::sequence()
            ->push([])
            ->push([
                [
                    'display_name' => 'Sampaloc, Manila, Metro Manila, Philippines',
                    'lat' => '14.6137',
                    'lon' => '120.9961',
                ],
            ]),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('address', '3301 2nd St. Vmapa, Brgy 601, Sampaloc, Manila')
        ->call('searchAddress')
        ->assertSet('addressSuggestions.0.label', 'Approximate area: Sampaloc, Manila, Metro Manila, Philippines')
        ->assertSet('address', '3301 2nd St. Vmapa, Brgy 601, Sampaloc, Manila')
        ->assertSet('addressLatitude', 14.6137)
        ->assertSet('addressLongitude', 120.9961)
        ->assertDispatched('toast-show', function (string $event, array $params): bool {
            return data_get($params, 'dataset.variant') === 'warning'
                && data_get($params, 'slots.text') === 'We found the nearby area. Drag the map pin to confirm the exact service location.';
        });

    Sleep::assertSleptTimes(1);
    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => $request['q'] === 'Sampaloc, Manila');
});

test('address search explains that a customer can continue manually when the provider is unavailable', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Http::fake([
        'https://nominatim.openstreetmap.org/search*' => Http::failedConnection(),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('address', '3301 2nd St. V. Mapa, Sampaloc, Manila')
        ->call('searchAddress')
        ->assertSet('addressSuggestions', [])
        ->assertSet('address', '3301 2nd St. V. Mapa, Sampaloc, Manila')
        ->assertDispatched('toast-show', function (string $event, array $params): bool {
            return data_get($params, 'dataset.variant') === 'danger'
                && data_get($params, 'slots.text') === 'We could not search the map right now. Enter the service address manually to continue.';
        });
});

test('desktop booking exposes the draggable auto-location map', function () {
    $this->seed();
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->call('openBookingFlow', 'quick')
        ->call('chooseServiceCategory', 'Electronics')
        ->call('chooseServiceType', 'electronics-battery')
        ->set('description', 'The battery drains too quickly.')
        ->call('nextBookingStep')
        ->assertSee('data-app-customer-booking-map', false)
        ->assertSee('data-map-auto-locate="true"', false)
        ->assertSee('data-map-draggable="true"', false)
        ->assertSee('data-app-map-latitude', false)
        ->assertSee('Drag the pin to fine-tune');
});

test('customer coordinates automatically update the service address through reverse geocoding', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'display_name' => 'Makati City, Metro Manila, Philippines',
            'lat' => '14.5547',
            'lon' => '121.0244',
        ]),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->call('openMobileQuickBook')
        ->call('updateLocationFromCoordinates', 14.5547, 121.0244)
        ->assertSet('address', 'Makati City, Metro Manila, Philippines')
        ->assertSet('addressLatitude', 14.5547)
        ->assertSet('addressLongitude', 121.0244);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/reverse')
        && (float) $request['lat'] === 14.5547
        && (float) $request['lon'] === 121.0244);
});

test('customer can keep the pin coordinates when reverse geocoding is unavailable', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([], 503),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->call('openMobileQuickBook')
        ->call('updateLocationFromCoordinates', 14.5547, 121.0244)
        ->assertSet('address', '')
        ->assertSet('addressLatitude', 14.5547)
        ->assertSet('addressLongitude', 121.0244)
        ->assertHasErrors('address')
        ->set('address', 'Manual address')
        ->assertHasNoErrors('address');
});

test('replaying a customer booking request reuses the existing booking', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    ServiceCatalog::create([
        'code' => 'plumbing',
        'name' => 'Plumbing',
        'category' => 'Home repair',
        'base_price' => 500,
        'is_active' => true,
    ]);
    $requestKey = '22222222-2222-4222-8222-222222222222';

    $component = Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'book-service'])
        ->set('bookingIdempotencyKey', $requestKey)
        ->set('customerPhone', '09171234567')
        ->set('serviceType', 'plumbing')
        ->set('address', 'Makati City')
        ->call('createBooking');

    $component
        ->set('bookingIdempotencyKey', $requestKey)
        ->set('customerPhone', '09171234567')
        ->set('serviceType', 'plumbing')
        ->set('address', 'Makati City')
        ->call('createBooking');

    expect(DB::table('bookings')->where('user_id', $customer->id)->count())->toBe(1);
});

test('customers can cancel an eligible booking with a reason', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $bookingId = customerBooking($customer->id, 'assigned', $technician->id);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'my-bookings'])
        ->call('prepareCancellation', $bookingId)
        ->set('cancellationReason', 'My schedule changed.')
        ->call('cancelBooking');

    expect(DB::table('bookings')->where('id', $bookingId)->first())
        ->status->toBe('cancelled')
        ->cancellation_reason->toBe('My schedule changed.')
        ->assigned_technician_id->toBe($technician->id);
});

test('customers can cancel a booking after service has started', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $bookingId = customerBooking($customer->id, 'in_progress', $technician->id);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'overview'])
        ->assertSee('Cancel booking')
        ->call('prepareCancellation', $bookingId)
        ->set('cancellationReason', 'I need to stop the service.')
        ->call('cancelBooking');

    expect(DB::table('bookings')->where('id', $bookingId)->first())
        ->status->toBe('cancelled')
        ->assigned_technician_id->toBe($technician->id)
        ->and($technician->notifications()->where('type', BookingStatusChanged::class)->count())->toBe(1);
});

test('customers can approve a quotation attached to their booking', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $bookingId = customerBooking($customer->id, 'in_progress', $technician->id);
    $quotationId = DB::table('quotations')->insertGetId([
        'booking_id' => $bookingId,
        'technician_id' => $technician->id,
        'assessment_notes' => 'Replace the damaged valve.',
        'labor_amount' => 800,
        'materials_amount' => 450,
        'total_amount' => 1250,
        'status' => 'awaiting_approval',
        'sent_at' => now(),
        'responded_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'quotations'])
        ->assertSee('Approve')
        ->call('respondToQuotation', $quotationId, 'approved');

    expect(DB::table('quotations')->where('id', $quotationId)->value('status'))->toBe('approved');
});

test('the duplicate addresses module is no longer exposed', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)
        ->get(route('customer.module', ['module' => 'addresses']))
        ->assertNotFound();
});

test('customers can submit one review for a completed booking', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $bookingId = customerBooking($customer->id, 'completed', $technician->id);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'ratings-reviews'])
        ->call('startReview', $bookingId)
        ->set('reviewRating', 5)
        ->set('reviewComment', 'Great service.')
        ->call('createReview')
        ->assertSet('reviewBookingId', null);

    expect(DB::table('reviews')->where('booking_id', $bookingId)->first())
        ->customer_id->toBe($customer->id)
        ->rating->toBe(5)
        ->comment->toBe('Great service.');
});

test('a duplicate review submission returns a stable response instead of a server error', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $bookingId = customerBooking($customer->id, 'completed', $technician->id);
    Review::create([
        'booking_id' => $bookingId,
        'customer_id' => $customer->id,
        'technician_id' => $technician->id,
        'rating' => 5,
        'comment' => 'Already submitted.',
        'status' => 'published',
    ]);

    $this->actingAs($customer);
    $component = new ModulePage;
    $component->reviewBookingId = $bookingId;
    $component->reviewRating = 4;
    $component->reviewComment = 'A concurrent duplicate.';

    expect(fn () => $component->createReview())->not->toThrow(HttpException::class);

    expect(DB::table('reviews')->where('booking_id', $bookingId)->count())->toBe(1);
});

test('customers can submit a support request and cannot open another customers booking', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $bookingId = customerBooking($otherCustomer->id);

    $this->actingAs($customer);

    expect(fn () => (new ModulePage)->openBooking($bookingId))
        ->toThrow(HttpException::class);

    Livewire::actingAs($customer)
        ->test(ModulePage::class, ['module' => 'support'])
        ->set('supportSubject', 'Booking question')
        ->set('supportCategory', 'booking')
        ->set('supportPriority', 'normal')
        ->set('supportMessage', 'Can you help with my booking?')
        ->call('createSupportTicket');

    expect(DB::table('support_tickets')->where('user_id', $customer->id)->first())
        ->subject->toBe('Booking question')
        ->status->toBe('open');
});
