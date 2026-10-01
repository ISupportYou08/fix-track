<?php

use App\Livewire\SuperAdmin\ModulePage as AdminModulePage;
use App\Models\User;
use App\Models\WalkInEntry;
use Livewire\Livewire;

test('completing a walk-in creates cash checkout and a private receipt', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $admin = User::factory()->create(['role' => 'superadmin']);
    $entry = WalkInEntry::factory()->create([
        'user_id' => $customer->id,
        'technician_id' => $technician->id,
        'status' => 'serving',
    ]);

    $entry->transitionTo('completed', $technician);
    $payment = $entry->payment()->firstOrFail();
    expect($payment->status)->toBe('pending');

    Livewire::actingAs($admin)->test(AdminModulePage::class, ['module' => 'payments-revenue'])
        ->assertSee('Walk-in cash checkout')
        ->call('openWalkInCheckout', $payment->id)
        ->set('checkoutAmount', '875.50')
        ->call('recordWalkInCash');

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->amount)->toBe('875.50');

    $this->actingAs($customer)->get(route('walk-ins.receipt', $entry))->assertOk()->assertSee('875.50');
    $this->actingAs($otherCustomer)->get(route('walk-ins.receipt', $entry))->assertForbidden();
});
