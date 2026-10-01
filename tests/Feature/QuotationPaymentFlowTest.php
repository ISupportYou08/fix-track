<?php

use App\Models\Booking;
use App\Models\Quotation;
use App\Models\ServiceCatalog;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('a quoted booking cannot complete before approval and bills the approved total', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    ServiceCatalog::query()->create(['code' => 'plumbing', 'name' => 'Plumbing', 'category' => 'Home', 'base_price' => 500, 'is_active' => true]);
    $booking = Booking::query()->create([
        'user_id' => $customer->id,
        'assigned_technician_id' => $technician->id,
        'reference' => 'QUOTE-001',
        'customer_name' => $customer->name,
        'customer_phone' => '09171234567',
        'service_type' => 'plumbing',
        'booking_type' => 'quick',
        'status' => 'in_progress',
        'address' => 'Quezon City',
    ]);
    $quotation = Quotation::query()->create([
        'booking_id' => $booking->id,
        'technician_id' => $technician->id,
        'assessment_notes' => 'Replacement parts needed.',
        'labor_amount' => 700,
        'materials_amount' => 250,
        'total_amount' => 950,
        'status' => 'awaiting_approval',
    ]);

    expect(fn () => $booking->transitionTo('completed', $technician))->toThrow(HttpException::class);
    $quotation->update(['status' => 'rejected']);
    expect(fn () => $booking->transitionTo('completed', $technician))->toThrow(HttpException::class);

    $quotation->update(['status' => 'approved']);
    $booking->transitionTo('completed', $technician);
    $payment = $booking->ensurePayment();

    expect($payment->amount)->toBe('950.00')
        ->and($payment->status)->toBe('pending')
        ->and($booking->fresh()->status)->toBe('completed');
});
