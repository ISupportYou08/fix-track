<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CompleteGoogleRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteGoogleRegistrationRequest;
use App\Models\ServiceCatalog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoogleRegistrationController extends Controller
{
    public function show(): View
    {
        return view('pages.auth.register', [
            'serviceCatalog' => ServiceCatalog::activeCatalog(),
            'googleRegistration' => session('google_registration'),
        ]);
    }

    public function store(
        CompleteGoogleRegistrationRequest $request,
        CompleteGoogleRegistration $completeRegistration,
    ): RedirectResponse {
        /** @var array{google_id: string, email: string, name: string, first_name: string, surname: string, picture: string|null, expires_at: int} $googleProfile */
        $googleProfile = $request->session()->get('google_registration');
        $user = $completeRegistration->execute($request->validated(), $googleProfile);

        $request->session()->forget('google_registration');
        Auth::login($user, remember: true);
        $request->session()->regenerate();
        event(new Registered($user));

        if ($user->isTechnician()) {
            return redirect()->route('technician.module')->with(
                'status',
                __('Your Google email is verified. Your technician application is waiting for staff review.'),
            );
        }

        $request->session()->flash(
            'google_status',
            __('Your customer account was created and connected to Google.'),
        );

        return redirect()->route('customer.module');
    }
}
