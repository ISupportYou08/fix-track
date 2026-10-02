<?php

use App\Livewire\Customer\ModulePage as CustomerModulePage;
use App\Livewire\Technician\ModulePage as TechnicianModulePage;
use App\Models\Booking;
use App\Models\BookingItemAnalysis;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

function fakeVisionAnalysis(array $overrides = [], string $responsePrefix = ''): void
{
    $analysis = array_merge([
        'supported' => true,
        'detected_item_name' => 'Smartphone',
        'service_code' => 'electronics-battery',
        'confidence' => 0.93,
        'explanation' => 'The image shows a smartphone, which matches an electronics repair service.',
    ], $overrides);

    Http::fake([
        'api.cloudflare.com/client/v4/accounts/*/ai/run/*' => Http::response([
            'result' => [
                'response' => $responsePrefix.json_encode($analysis, JSON_THROW_ON_ERROR),
            ],
            'success' => true,
            'errors' => [],
            'messages' => [],
        ]),
    ]);
}

function fakeItemImage(string $name = 'item.png'): UploadedFile
{
    $onePixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1+QAAAAASUVORK5CYII=', true);

    return UploadedFile::fake()->createWithContent($name, $onePixelPng)->mimeType('image/png');
}

test('the dashboard green action opens the AI item image flow', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $component = Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview']);

    $component
        ->assertSee('data-customer-dashboard-action="ai"', false)
        ->assertSee('wire:click="openAiBookingFlow"', false)
        ->call('openAiBookingFlow')
        ->assertSet('showAiBookingFlow', true)
        ->assertSet('aiAnalysisStatus', 'upload')
        ->assertSee('AI assisted booking')
        ->assertSee('data-ai-item-image-input', false)
        ->assertSee('data-ai-item-camera-button', false)
        ->assertSee('data-ai-camera-preview', false)
        ->assertSee('wire:model="aiItemImages"', false)
        ->assertSee('multiple', false)
        ->assertSee('Take front photo')
        ->assertDontSee('data-ai-analyze-button', false)
        ->assertDontSee('capture="environment"', false);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', [fakeItemImage('first.png'), fakeItemImage('second.png')])
        ->assertDontSee('data-ai-analyze-button', false);
});

test('the take photo control uses the browser camera and sends the captured frame to Livewire', function () {
    $cameraScript = file_get_contents(resource_path('js/ai-item-camera.js'));
    $analyzerSource = file_get_contents(app_path('Actions/Bookings/AnalyzeServiceItemImage.php'));

    expect($cameraScript)
        ->toContain('navigator.mediaDevices.getUserMedia')
        ->toContain("facingMode: { ideal: 'environment' }")
        ->toContain("canvas.toBlob(resolve, 'image/jpeg', 0.9)")
        ->toContain('new DataTransfer()')
        ->toContain("dispatchEvent(new Event('change', { bubbles: true }))")
        ->toContain('track.stop()');

    expect($analyzerSource)
        ->toContain('Http::pool')
        ->toContain('concurrency: 3')
        ->toContain('->timeout(20)');
});

test('customer can retake one AI item photo and analysis waits for its replacement', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $component = Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', [
            fakeItemImage('front.png'),
            fakeItemImage('side.png'),
            fakeItemImage('back.png'),
        ])
        ->assertSee('data-ai-analyze-button', false)
        ->assertSee('data-ai-retake-image', false)
        ->call('retakeAiItemImage', 0)
        ->assertCount('aiItemImages', 2)
        ->assertSet('aiRetakeImageIndex', 0)
        ->assertSee('Front view')
        ->assertDontSee('data-ai-analyze-button', false)
        ->assertDispatched('ai-item-camera-start');

    $component
        ->set('aiItemImages.2', fakeItemImage('new-front.png'))
        ->assertCount('aiItemImages', 3)
        ->assertSet('aiRetakeImageIndex', null)
        ->assertSee('data-ai-analyze-button', false);
});

test('a confirmed image analysis prefills manual home service and is saved with the booking', function () {
    $this->seed();
    Storage::fake('local');
    config()->set('services.cloudflare.api_token', 'test-token');
    config()->set('services.cloudflare.account_id', 'test-account');
    config()->set('services.cloudflare.vision_model', '@cf/meta/llama-3.2-11b-vision-instruct');
    fakeVisionAnalysis(responsePrefix: 'Analysis complete. Answer: ');
    Carbon::setTestNow('2026-10-01 10:00:00');
    $customer = User::factory()->create(['role' => 'customer']);
    $images = [
        fakeItemImage('phone-front.png'),
        fakeItemImage('phone-back.png'),
        fakeItemImage('phone-side.png'),
    ];

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', $images)
        ->assertSee('3 of 3 images ready')
        ->assertSee('Front view')
        ->assertSee('Side view')
        ->assertSee('Back view')
        ->assertSee('data-ai-analyze-button', false)
        ->call('analyzeAiItemImage')
        ->assertHasNoErrors()
        ->assertSet('aiAnalysisStatus', 'result')
        ->assertSet('aiDetectedItemName', 'Smartphone')
        ->assertSet('aiSuggestedServiceCode', 'electronics-battery')
        ->assertSee('Continue with this result')
        ->call('continueWithAiAnalysis')
        ->assertSet('showAiBookingFlow', false)
        ->assertSet('showBookingFlow', true)
        ->assertSet('bookingFlow', 'manual')
        ->assertSet('manualServiceMode', 'home-service')
        ->assertSet('bookingStep', 2)
        ->assertSet('serviceCategory', 'Electronics')
        ->assertSet('serviceType', 'electronics-battery')
        ->assertSee('AI identified: Smartphone')
        ->set('description', 'The phone battery drains within one hour.')
        ->call('nextBookingStep')
        ->assertSet('bookingStep', 3)
        ->set('customerPhone', '9171234567')
        ->set('address', '123 Rizal Street, Quezon City')
        ->set('scheduledAt', '2026-10-02T10:00')
        ->call('submitBookingFlow')
        ->assertHasNoErrors();

    $booking = Booking::query()->whereBelongsTo($customer, 'customer')->firstOrFail();
    $analysis = $booking->itemAnalysis()->firstOrFail();

    expect($booking->service_type)->toBe('electronics-battery')
        ->and($booking->booking_type)->toBe('scheduled')
        ->and($booking->status)->toBe('matching')
        ->and($analysis->detected_item_name)->toBe('Smartphone')
        ->and($analysis->suggested_service_code)->toBe('electronics-battery')
        ->and((float) $analysis->confidence)->toBe(0.93)
        ->and($analysis->imageFiles())->toHaveCount(3)
        ->and($analysis->model)->toBe('@cf/meta/llama-3.2-11b-vision-instruct');
    foreach ($analysis->imageFiles() as $image) {
        Storage::disk('local')->assertExists($image['path']);
    }

    Http::assertSent(function ($request): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.cloudflare.com/client/v4/accounts/test-account/ai/run/@cf/meta/llama-3.2-11b-vision-instruct'
            && str_starts_with($payload['image'], 'data:image/png;base64,')
            && str_contains($payload['prompt'], 'electronics-battery')
            && $payload['max_tokens'] === 400;
    });
    Http::assertSentCount(3);
});

test('a possible catalog match can prefill manual booking for customer review', function () {
    $this->seed();
    config()->set('services.cloudflare.api_token', 'test-token');
    config()->set('services.cloudflare.account_id', 'test-account');
    config()->set('services.cloudflare.vision_model', '@cf/meta/llama-3.2-11b-vision-instruct');
    fakeVisionAnalysis([
        'detected_item_name' => 'Smartphone',
        'service_code' => 'electronics-battery',
        'confidence' => 0.42,
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', [
            fakeItemImage('phone-front.png'),
            fakeItemImage('phone-back.png'),
            fakeItemImage('phone-side.png'),
        ])
        ->call('analyzeAiItemImage')
        ->assertSet('aiAnalysisStatus', 'unsupported')
        ->assertSet('aiSuggestedServiceCode', 'electronics-battery')
        ->assertSee('Possible item: Smartphone')
        ->assertSee('Suggested service: Battery replacement · Electronics')
        ->assertSee('data-ai-book-possible-item', false)
        ->call('continueWithPossibleAiMatch')
        ->assertSet('showAiBookingFlow', false)
        ->assertSet('showBookingFlow', true)
        ->assertSet('bookingFlow', 'manual')
        ->assertSet('manualServiceMode', 'home-service')
        ->assertSet('bookingStep', 2)
        ->assertSet('serviceCategory', 'Electronics')
        ->assertSet('serviceType', 'electronics-battery')
        ->assertSet('aiAnalysisConfirmed', false)
        ->assertSee('data-ai-possible-service-prefill', false)
        ->assertSee('Review or change them before continuing.')
        ->assertDispatched('ai-item-camera-stop');
});

test('a provider uncertain result still offers a valid catalog service for manual review', function () {
    $this->seed();
    config()->set('services.cloudflare.api_token', 'test-token');
    config()->set('services.cloudflare.account_id', 'test-account');
    config()->set('services.cloudflare.vision_model', '@cf/meta/llama-3.2-11b-vision-instruct');
    fakeVisionAnalysis([
        'supported' => false,
        'detected_item_name' => 'Smartphone',
        'service_code' => 'electronics-battery',
        'confidence' => 0.72,
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', [
            fakeItemImage('phone-front.png'),
            fakeItemImage('phone-side.png'),
            fakeItemImage('phone-back.png'),
        ])
        ->call('analyzeAiItemImage')
        ->assertSet('aiAnalysisStatus', 'unsupported')
        ->assertSet('aiSuggestedServiceCode', 'electronics-battery')
        ->assertSee('Suggested service: Battery replacement · Electronics')
        ->assertSee('data-ai-book-possible-item', false);
});

test('an unsupported AI service result cannot bypass the live catalog', function () {
    config()->set('services.cloudflare.api_token', 'test-token');
    config()->set('services.cloudflare.account_id', 'test-account');
    config()->set('services.cloudflare.vision_model', '@cf/meta/llama-3.2-11b-vision-instruct');
    fakeVisionAnalysis([
        'detected_item_name' => 'Industrial turbine',
        'service_code' => 'industrial-turbine-repair',
        'confidence' => 0.98,
    ]);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->set('aiItemImages', [
            fakeItemImage('turbine-front.png'),
            fakeItemImage('turbine-back.png'),
            fakeItemImage('turbine-side.png'),
        ])
        ->call('analyzeAiItemImage')
        ->assertSet('aiAnalysisStatus', 'unsupported')
        ->assertSet('aiSuggestedServiceCode', '')
        ->assertSee('We could not confidently match this item.')
        ->assertDontSee('data-ai-book-possible-item', false)
        ->assertDontSee('Continue with this result')
        ->assertSee('Retake all photos')
        ->assertSee('data-ai-retake-all', false)
        ->call('retakeAllAiItemImages')
        ->assertCount('aiItemImages', 0)
        ->assertSet('aiAnalysisStatus', 'upload')
        ->assertDontSee('data-ai-analyze-button', false)
        ->assertDispatched('ai-item-camera-start');
});

test('AI analysis has validation and provider failure fallbacks', function () {
    config()->set('services.cloudflare.api_token', null);
    config()->set('services.cloudflare.account_id', null);
    $customer = User::factory()->create(['role' => 'customer']);

    Livewire::actingAs($customer)
        ->test(CustomerModulePage::class, ['module' => 'overview'])
        ->call('openAiBookingFlow')
        ->call('analyzeAiItemImage')
        ->assertHasErrors(['aiItemImages' => 'required'])
        ->set('aiItemImages', [
            fakeItemImage('one.png'),
            fakeItemImage('two.png'),
            fakeItemImage('three.png'),
            fakeItemImage('four.png'),
        ])
        ->call('analyzeAiItemImage')
        ->assertHasErrors(['aiItemImages' => 'size'])
        ->call('resetAiItemAnalysis')
        ->set('aiItemImages', [
            fakeItemImage('one.png'),
            fakeItemImage('two.png'),
            fakeItemImage('three.png'),
        ])
        ->call('analyzeAiItemImage')
        ->assertSet('aiAnalysisStatus', 'error')
        ->assertSee('We could not analyze these images right now.');
});

test('confirmed item images are private and visible to matching technicians', function () {
    Storage::fake('local');
    $customer = User::factory()->create(['role' => 'customer']);
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $matchingTechnician = User::factory()->create(['role' => 'technician', 'account_status' => 'active']);
    $otherTechnician = User::factory()->create(['role' => 'technician', 'account_status' => 'active']);
    $matchingTechnician->technicianVerification()->create([
        'status' => 'approved',
        'risk_level' => 'low',
        'service_categories' => ['electronics-battery'],
        'years_experience' => 3,
    ]);
    $otherTechnician->technicianVerification()->create([
        'status' => 'approved',
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 3,
    ]);
    $booking = Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'AI-PRIVATE-001',
        'customer_name' => $customer->name,
        'customer_phone' => '+639171234567',
        'service_type' => 'electronics-battery',
        'booking_type' => 'scheduled',
        'status' => 'matching',
        'address' => 'Quezon City',
        'description' => 'Battery drains quickly.',
        'scheduled_at' => now()->addDay(),
    ]);
    Storage::disk('local')->put('booking-item-images/private-phone.jpg', 'private-image');
    Storage::disk('local')->put('booking-item-images/private-phone-back.jpg', 'private-image-back');
    $analysis = BookingItemAnalysis::factory()->for($booking)->create([
        'image_path' => 'booking-item-images/private-phone.jpg',
        'images' => [
            [
                'path' => 'booking-item-images/private-phone.jpg',
                'original_name' => 'phone-front.jpg',
                'mime_type' => 'image/jpeg',
            ],
            [
                'path' => 'booking-item-images/private-phone-back.jpg',
                'original_name' => 'phone-back.jpg',
                'mime_type' => 'image/jpeg',
            ],
        ],
        'detected_item_name' => 'Smartphone',
    ]);

    $this->actingAs($customer)->get(route('booking-item-analyses.image', $analysis))->assertOk();
    $this->actingAs($customer)->get(route('booking-item-analyses.image', ['analysis' => $analysis, 'image' => 1]))->assertOk();
    $this->actingAs($customer)->get(route('booking-item-analyses.image', ['analysis' => $analysis, 'image' => 2]))->assertNotFound();
    $this->actingAs($matchingTechnician)->get(route('booking-item-analyses.image', $analysis))->assertOk();
    $this->actingAs($otherCustomer)->get(route('booking-item-analyses.image', $analysis))->assertForbidden();
    $this->actingAs($otherTechnician)->get(route('booking-item-analyses.image', $analysis))->assertForbidden();

    Livewire::actingAs($matchingTechnician)
        ->test(TechnicianModulePage::class, ['module' => 'job-requests'])
        ->call('openRequestDetails', $booking->id)
        ->assertSee('data-technician-item-analysis', false)
        ->assertSee('Smartphone');
});
