<?php

use App\Actions\WalkIns\CreateWalkInEntry;
use App\Livewire\Customer\ModulePage as CustomerModulePage;
use App\Livewire\Technician\ModulePage as TechnicianModulePage;
use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function createWalkInFor(User $customer, array $attributes = []): WalkInEntry
{
    return app(CreateWalkInEntry::class)->execute($customer, [
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => '09170000000',
        'service_type' => 'aircon',
        'notes' => 'Aircon is not cooling.',
        'technician_id' => null,
        ...$attributes,
    ]);
}

test('a customer joins an empty Walk-In queue with a unique numbered cash ticket', function () {
    $ticket = createWalkInFor(User::factory()->create(['role' => 'customer']));

    expect(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(1)
        ->and($ticket->queue_number)->toBe('W'.str_pad((string) $ticket->id, 3, '0', STR_PAD_LEFT))
        ->and($ticket->payment_method)->toBe('cash')
        ->and($ticket->queuePosition())->toBe(1)
        ->and($ticket->statusHistory()->where('to_status', 'waiting')->exists())->toBeTrue();
});

test('a third customer can fill the final available Walk-In slot', function () {
    createWalkInFor(User::factory()->create(['role' => 'customer']));
    createWalkInFor(User::factory()->create(['role' => 'customer']));

    $third = createWalkInFor(User::factory()->create(['role' => 'customer']));

    expect(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(3)
        ->and($third->queuePosition())->toBe(3);
});

test('a fourth customer cannot exceed the server-side Walk-In capacity', function () {
    foreach (range(1, WalkInEntry::MAX_ACTIVE) as $unused) {
        createWalkInFor(User::factory()->create(['role' => 'customer']));
    }

    expect(fn () => createWalkInFor(User::factory()->create(['role' => 'customer'])))
        ->toThrow(ValidationException::class, 'Walk-In Queue is currently full');

    expect(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(3);
});

test('stale Walk-In tickets expire without being deleted or counted as active', function () {
    $ticket = WalkInEntry::factory()->create([
        'status' => 'waiting',
        'checked_in_at' => now()->subDays(2),
    ]);
    $ticket->recordInitialStatus($ticket->customer);

    $this->artisan('walk-ins:expire-stale')->assertSuccessful();

    expect($ticket->refresh()->status)->toBe('no_show')
        ->and(WalkInEntry::query()->whereKey($ticket->id)->exists())->toBeTrue()
        ->and($ticket->statusHistory()->where('to_status', 'no_show')->exists())->toBeTrue()
        ->and(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(0);
});

test('the maintenance command closes duplicate and excess active tickets without deleting history', function () {
    $duplicateCustomer = User::factory()->create(['role' => 'customer']);
    WalkInEntry::factory()->count(2)->create(['user_id' => $duplicateCustomer->id]);
    WalkInEntry::factory()->count(3)->create();

    $this->artisan('walk-ins:expire-stale')->assertSuccessful();

    expect(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(WalkInEntry::MAX_ACTIVE)
        ->and(WalkInEntry::query()->count())->toBe(5)
        ->and(WalkInEntry::query()->where('status', 'cancelled')->count())->toBe(2);
});

test('customer cancellation keeps history, stops sharing, and frees a Walk-In slot', function () {
    $customers = collect(range(1, 4))->map(fn (): User => User::factory()->create(['role' => 'customer']));
    $tickets = $customers->take(3)->map(fn (User $customer): WalkInEntry => createWalkInFor($customer));
    $cancelledTicket = $tickets->first();

    Livewire::actingAs($customers->first())
        ->test(CustomerModulePage::class, ['module' => 'walk-in-queue'])
        ->call('updateWalkInLocation', $cancelledTicket->id, 14.5995, 120.9842)
        ->call('prepareWalkInCancellation', $cancelledTicket->id)
        ->set('walkInCancellationReason', 'I cannot visit today.')
        ->call('cancelWalkIn');

    $cancelledTicket->refresh();
    expect($cancelledTicket->status)->toBe('cancelled')
        ->and($cancelledTicket->cancelled_at)->not->toBeNull()
        ->and($cancelledTicket->cancelled_by)->toBe($customers->first()->id)
        ->and($cancelledTicket->location_sharing_enabled)->toBeFalse()
        ->and($cancelledTicket->customer_latitude)->toBeNull()
        ->and($cancelledTicket->statusHistory()->where('to_status', 'cancelled')->exists())->toBeTrue();

    createWalkInFor($customers->last());
    expect(WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count())->toBe(3);
});

test('only the owning customer can update a Walk-In live location', function () {
    $owner = User::factory()->create(['role' => 'customer']);
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $ticket = createWalkInFor($owner);

    Livewire::actingAs($owner)
        ->test(CustomerModulePage::class, ['module' => 'walk-in-queue'])
        ->call('updateWalkInLocation', $ticket->id, 14.5995, 120.9842);

    expect((float) $ticket->refresh()->customer_latitude)->toBe(14.5995)
        ->and($ticket->location_sharing_enabled)->toBeTrue();

    expect(fn () => Livewire::actingAs($otherCustomer)
        ->test(CustomerModulePage::class, ['module' => 'walk-in-queue'])
        ->call('updateWalkInLocation', $ticket->id, 14.5, 121.0))
        ->toThrow(ModelNotFoundException::class);
});

test('assigned technicians see Walk-In details and preserve completed ticket history', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => 'active',
        'latitude' => 14.61,
        'longitude' => 121.01,
    ]);
    $ticket = createWalkInFor($customer, ['technician_id' => $technician->id]);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'walk-in-queue'])
        ->call('updateWalkInLocation', $ticket->id, 14.5995, 120.9842);

    $this->actingAs($technician)
        ->getJson(route('realtime.snapshot', ['scope' => 'technician-walk-in']))
        ->assertOk()
        ->assertJsonPath('counts.active', 1);

    Livewire::actingAs($technician)
        ->test(TechnicianModulePage::class, ['module' => 'walk-in'])
        ->assertSee($ticket->queue_number)
        ->call('openWalkInDetails', $ticket->id)
        ->assertSet('showWalkInDetails', true)
        ->assertSee('View Direction')
        ->assertSee('Payment method')
        ->call('updateWalkInStatus', $ticket->id, 'serving')
        ->call('updateWalkInStatus', $ticket->id, 'completed');

    expect($ticket->refresh()->status)->toBe('completed')
        ->and($ticket->location_sharing_enabled)->toBeFalse()
        ->and($ticket->statusHistory()->pluck('to_status')->all())->toContain('serving', 'completed');
});

test('technician cancellation stores its reason and keeps the Walk-In ticket visible in history', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $ticket = createWalkInFor($customer, ['technician_id' => $technician->id]);

    Livewire::actingAs($technician)
        ->test(TechnicianModulePage::class, ['module' => 'walk-in'])
        ->call('prepareWalkInCancellation', $ticket->id)
        ->set('walkInCancellationReason', 'Technician unavailable.')
        ->call('cancelSelectedWalkIn')
        ->assertSee($ticket->queue_number);

    expect($ticket->refresh()->status)->toBe('cancelled')
        ->and($ticket->cancellation_reason)->toBe('Technician unavailable.')
        ->and($ticket->cancelled_by)->toBe($technician->id)
        ->and(WalkInEntry::query()->whereKey($ticket->id)->exists())->toBeTrue();
});

test('a technician cannot access a Walk-In ticket assigned to another technician', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $assignedTechnician = User::factory()->create(['role' => 'technician']);
    $otherTechnician = User::factory()->create(['role' => 'technician']);
    $ticket = createWalkInFor($customer, ['technician_id' => $assignedTechnician->id]);

    expect(fn () => Livewire::actingAs($otherTechnician)
        ->test(TechnicianModulePage::class, ['module' => 'walk-in'])
        ->call('openWalkInDetails', $ticket->id))
        ->toThrow(ModelNotFoundException::class);
});
