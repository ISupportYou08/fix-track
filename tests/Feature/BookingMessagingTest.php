<?php

use App\Livewire\BookingMessenger;
use App\Livewire\Technician\ModulePage as TechnicianModulePage;
use App\Models\AssistantMessage;
use App\Models\Booking;
use App\Models\BookingChat;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function messageBooking(User $customer): Booking
{
    return Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'CHAT-'.fake()->unique()->numerify('#####'),
        'customer_name' => $customer->name,
        'customer_phone' => '09171234567',
        'service_type' => 'plumbing',
        'booking_type' => 'quick',
        'status' => 'matching',
        'address' => 'Quezon City',
    ]);
}

test('accepting a booking creates one technician conversation and opening message', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => 'active',
        'availability_status' => 'available',
    ]);
    ServiceCatalog::query()->create(['code' => 'plumbing', 'name' => 'Plumbing', 'category' => 'Home', 'base_price' => 500, 'is_active' => true]);
    DB::table('technician_verifications')->insert([
        'user_id' => $technician->id,
        'status' => 'approved',
        'risk_level' => 'low',
        'service_categories' => json_encode(['plumbing']),
        'years_experience' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $booking = messageBooking($customer);

    Livewire::actingAs($technician)->test(TechnicianModulePage::class, ['module' => 'job-requests'])
        ->call('acceptRequest', $booking->id);

    $chat = BookingChat::query()->whereBelongsTo($booking)->firstOrFail();
    expect($chat->messages()->count())->toBe(1)
        ->and($chat->messages()->first()->sender_id)->toBe($technician->id)
        ->and($chat->messages()->first()->body)->toContain("I've accepted");

    $this->actingAs($customer)
        ->get(route('customer.module', ['module' => 'messages']))
        ->assertOk()
        ->assertSee('FixTrack Assistant')
        ->assertSee($technician->name);
    $this->actingAs($technician)
        ->get(route('technician.module', ['module' => 'messages']))
        ->assertOk()
        ->assertSee($customer->name);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->assertSee('FixTrack Assistant')
        ->assertSee($technician->name)
        ->call('selectChat', $chat->id)
        ->assertSee("I've accepted")
        ->set('draft', 'Thank you. Please let me know when you arrive.')
        ->call('send');

    Livewire::actingAs($technician)->test(BookingMessenger::class)
        ->call('selectChat', $chat->id)
        ->assertSee('Please let me know when you arrive.');
});

test('booking messages are private to the booking participants', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $outsider = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $booking = messageBooking($customer);
    $booking->update(['assigned_technician_id' => $technician->id, 'status' => 'en_route']);
    $chat = BookingChat::query()->create(['booking_id' => $booking->id]);

    expect(fn () => Livewire::actingAs($outsider)->test(BookingMessenger::class)
        ->call('selectChat', $chat->id))->toThrow(ModelNotFoundException::class);
});

test('customers can use the assistant before a technician accepts', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->assertSee('FixTrack Assistant')
        ->set('draft', 'How do I book a repair?')
        ->call('send')
        ->assertSee('Book a Service');

    expect(DB::table('assistant_messages')->where('user_id', $customer->id)->count())->toBe(2);
});

test('the local assistant answers greetings and navigation questions instead of repeating generic help', function () {
    config()->set('services.openai.key', null);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->set('draft', 'hi')
        ->call('send')
        ->assertSee('Hi! What would you like help with in FixTrack?')
        ->set('draft', 'how to navigate this system?')
        ->call('send')
        ->assertSee('Home, Activity, Messages, and Account')
        ->assertSee('Book a Service')
        ->set('draft', 'How do I use FixTrack?')
        ->call('send')
        ->assertSee('Home, Activity, Messages, and Account');

    expect(AssistantMessage::query()->where('user_id', $customer->id)->where('role', 'assistant')->count())->toBe(3);
});

test('the local assistant prioritizes the specific task and asks for clarification when needed', function () {
    config()->set('services.openai.key', null);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->set('draft', 'How do I cancel a booking?')
        ->call('send')
        ->assertSee('Cancel booking')
        ->assertDontSee('choose quick or manual booking')
        ->set('draft', 'How do I use this app to cancel my booking?')
        ->call('send')
        ->assertSee('Enter a reason to confirm')
        ->set('draft', 'How can I message the technician who accepted?')
        ->call('send')
        ->assertSee('their conversation appears below this assistant')
        ->set('draft', 'Tell me about something unrelated')
        ->call('send')
        ->assertSee('I am not sure which FixTrack task you mean');
});

test('the local assistant gives technician-specific navigation guidance', function () {
    config()->set('services.openai.key', null);
    $technician = User::factory()->create(['role' => 'technician']);

    Livewire::actingAs($technician)->test(BookingMessenger::class)
        ->set('draft', 'How do I navigate this system?')
        ->call('send')
        ->assertSee('Job Requests')
        ->assertSee('My Jobs')
        ->assertDontSee('Home, Activity, Messages, and Account');
});

test('customer Messages opens with the assistant on mobile and the dashboard embeds a compact side panel', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $messagesPage = $this->actingAs($customer)
        ->get(route('customer.module', ['module' => 'messages']))
        ->assertOk()
        ->assertSee('customer-mobile-shell')
        ->assertSee('FixTrack Assistant')
        ->assertSee('Choose a booking contact');

    $document = new DOMDocument;
    @$document->loadHTML($messagesPage->getContent());
    $mobileAssistant = (new DOMXPath($document))->query('//*[@data-app-customer-mobile-shell]//*[contains(text(), "FixTrack Assistant")]');
    expect($mobileAssistant->length)->toBeGreaterThan(0);

    $dashboard = $this->actingAs($customer)
        ->get(route('customer.module'))
        ->assertOk()
        ->assertSee('Open FixTrack Support')
        ->assertSee('data-customer-reference-chat-widget', false)
        ->assertSee('data-customer-message-panel', false)
        ->assertSee('data-booking-messenger-layout="compact"', false)
        ->assertDontSee('Available Technicians');

    $document = new DOMDocument;
    @$document->loadHTML($dashboard->getContent());
    $dashboardElements = new DOMXPath($document);
    expect($dashboardElements->query('//*[@data-customer-reference-dashboard]//button[@aria-controls="customer-dashboard-messages"]')->length)->toBe(1)
        ->and($dashboardElements->query('//*[@data-customer-message-panel]//*[@data-booking-messenger-layout="compact"]')->length)->toBe(1)
        ->and($dashboardElements->query('//*[@data-customer-message-panel]//*[@data-chat-panel-header]')->length)->toBe(1)
        ->and($dashboardElements->query('//*[@data-customer-message-panel]//*[@data-chat-conversation-header]')->length)->toBe(1)
        ->and($dashboardElements->query('//*[@data-customer-message-panel]//*[@data-chat-composer]')->length)->toBe(1)
        ->and($dashboardElements->query('//*[@data-customer-reference-dashboard]//a[@aria-label="Open messages"]')->length)->toBe(0);
});

test('the compact dashboard messenger can answer without leaving the dashboard', function () {
    config()->set('services.openai.key', null);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)->test(BookingMessenger::class, ['compact' => true])
        ->assertSee('data-booking-messenger-layout="compact"', false)
        ->assertSee('data-booking-chat-rail', false)
        ->assertDontSee('data-booking-chat-head=', false)
        ->assertSee('FixTrack Assistant')
        ->set('draft', 'How do I navigate this system?')
        ->call('send')
        ->assertSee('Home, Activity, Messages, and Account');
});

test('recent booking chat heads appear below the side plus button and open their conversations', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $earlierTechnician = User::factory()->create(['role' => 'technician', 'name' => 'Earlier Technician']);
    $laterTechnician = User::factory()->create(['role' => 'technician', 'name' => 'Later Technician']);
    User::factory()->create(['role' => 'technician', 'name' => 'Unrelated Technician']);

    $earlierBooking = messageBooking($customer);
    $earlierBooking->update(['assigned_technician_id' => $earlierTechnician->id, 'status' => 'en_route']);
    $earlierChat = BookingChat::query()->create(['booking_id' => $earlierBooking->id]);
    $earlierChat->created_at = now()->subDays(2);
    $earlierChat->save();

    $laterBooking = messageBooking($customer);
    $laterBooking->update(['assigned_technician_id' => $laterTechnician->id, 'status' => 'en_route']);
    $laterChat = BookingChat::query()->create(['booking_id' => $laterBooking->id]);
    $olderMessage = $laterChat->messages()->create(['sender_id' => $laterTechnician->id, 'kind' => 'message', 'body' => 'Older conversation']);
    $olderMessage->created_at = now()->subHour();
    $olderMessage->save();
    $earlierChat->messages()->create(['sender_id' => $earlierTechnician->id, 'kind' => 'message', 'body' => 'Newest conversation']);

    $messenger = Livewire::actingAs($customer)->test(BookingMessenger::class, ['compact' => true])
        ->assertSee('data-booking-chat-head="'.$earlierChat->id.'"', false)
        ->assertSee('data-booking-chat-head="'.$laterChat->id.'"', false)
        ->assertDontSee('Newest conversation')
        ->assertDontSee('Unrelated Technician');

    $document = new DOMDocument;
    @$document->loadHTML($messenger->html());
    $rail = (new DOMXPath($document))->query('//*[@data-booking-chat-rail]')->item(0);
    $heads = (new DOMXPath($document))->query('.//button[@data-booking-chat-add]/following-sibling::div//button[@data-booking-chat-head]', $rail);
    expect($heads->length)->toBe(2)
        ->and($heads->item(0)->getAttribute('data-booking-chat-head'))->toBe((string) $earlierChat->id)
        ->and($heads->item(1)->getAttribute('data-booking-chat-head'))->toBe((string) $laterChat->id);

    $messenger->call('selectChat', $earlierChat->id)
        ->assertSee('Newest conversation')
        ->call('toggleContacts')
        ->assertSee('Accepted booking technicians')
        ->assertDontSee('Unrelated Technician');
});

test('the plus contact picker only shows technicians from accepted booking conversations', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $acceptedTechnician = User::factory()->create(['role' => 'technician', 'name' => 'Accepted Booking Technician']);
    User::factory()->create(['role' => 'technician', 'name' => 'Unrelated Technician']);
    $booking = messageBooking($customer);
    $booking->update(['assigned_technician_id' => $acceptedTechnician->id, 'status' => 'en_route']);
    BookingChat::query()->create(['booking_id' => $booking->id]);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->call('toggleContacts')
        ->assertSee('Accepted booking technicians')
        ->assertSee('Accepted Booking Technician')
        ->assertDontSee('Unrelated Technician');
});

test('technicians open Messages on their private assistant before customer conversations', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    $booking = messageBooking($customer);
    $booking->update(['assigned_technician_id' => $technician->id, 'status' => 'en_route']);
    $chat = BookingChat::query()->create(['booking_id' => $booking->id]);
    AssistantMessage::query()->create(['user_id' => $customer->id, 'role' => 'user', 'body' => 'Private customer question']);

    $this->actingAs($technician)
        ->get(route('technician.module', ['module' => 'messages']))
        ->assertOk()
        ->assertSee('FixTrack Assistant')
        ->assertSee($customer->name);

    Livewire::actingAs($technician)->test(BookingMessenger::class)
        ->assertSee('Hi! Ask me how to accept requests')
        ->assertDontSee('Private customer question')
        ->set('draft', 'How do I accept a job request?')
        ->call('send')
        ->assertSee('Open Job Requests')
        ->call('selectChat', $chat->id)
        ->call('selectAssistant')
        ->assertSee('Open Job Requests');

    expect(AssistantMessage::query()->where('user_id', $technician->id)->count())->toBe(2);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->assertSee('Private customer question')
        ->assertDontSee('Open Job Requests');
});

test('the configured assistant uses the provider response with private customer history', function () {
    config()->set('services.openai.key', 'test-key');
    config()->set('services.openai.model', 'gpt-4.1-mini');
    Http::fake(['api.openai.com/v1/responses' => Http::response([
        'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Open Book a Service to start.']]]],
    ])]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)->test(BookingMessenger::class)
        ->set('draft', 'How do I book?')
        ->call('send')
        ->assertSee('Open Book a Service to start.');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false
            && str_contains($request['instructions'], 'a navigation question needs navigation steps')
            && str_contains($request['instructions'], 'Book a Service')
            && $request['input'][0]['role'] === 'user'
            && $request['input'][0]['content'] === 'How do I book?';
    });
});

test('the configured assistant gives technicians role-specific guidance without customer history', function () {
    config()->set('services.openai.key', 'test-key');
    Http::fake(['api.openai.com/v1/responses' => Http::response([
        'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Review requests in Job Requests.']]]],
    ])]);
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);
    AssistantMessage::query()->create(['user_id' => $customer->id, 'role' => 'user', 'body' => 'Private customer question']);

    Livewire::actingAs($technician)->test(BookingMessenger::class)
        ->set('draft', 'Where are my new jobs?')
        ->call('send')
        ->assertSee('Review requests in Job Requests.');

    Http::assertSent(function ($request): bool {
        return str_contains($request['instructions'], 'for technicians')
            && count($request['input']) === 1
            && $request['input'][0]['content'] === 'Where are my new jobs?';
    });
});
