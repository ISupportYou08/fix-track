<?php

use App\Livewire\Customer\ModulePage as CustomerModulePage;
use App\Livewire\SuperAdmin\ModulePage as AdminModulePage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('customer and admin replies remain in a private support conversation', function () {
    Storage::fake('local');
    $customer = User::factory()->create(['role' => 'customer']);
    $outsider = User::factory()->create(['role' => 'customer']);
    $admin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($customer)->test(CustomerModulePage::class, ['module' => 'support'])
        ->set('supportSubject', 'Booking question')
        ->set('supportCategory', 'booking')
        ->set('supportPriority', 'normal')
        ->set('supportMessage', 'When will my technician arrive?')
        ->call('createSupportTicket');

    $ticket = SupportTicket::query()->where('user_id', $customer->id)->firstOrFail();
    expect($ticket->messages()->count())->toBe(1);

    Livewire::actingAs($admin)->test(AdminModulePage::class, ['module' => 'support-disputes'])
        ->call('openSupportEditor', $ticket->id)
        ->set('supportMessage', 'Please check the booking status in My Bookings.')
        ->call('saveSupportTicket');

    Livewire::actingAs($customer)->test(CustomerModulePage::class, ['module' => 'support'])
        ->call('openSupportConversation', $ticket->id)
        ->assertSee('Please check the booking status')
        ->set('supportReply', 'Thank you. Here is a photo of the issue.')
        ->set('supportAttachment', UploadedFile::fake()->create('issue.pdf', 10, 'application/pdf'))
        ->call('replyToSupportTicket');

    expect($ticket->messages()->count())->toBe(3);
    $attachment = $ticket->messages()->latest('id')->firstOrFail();
    $this->actingAs($customer)->get(route('support.attachment', $attachment))->assertOk();
    $this->actingAs($outsider)->get(route('support.attachment', $attachment))->assertForbidden();
});
