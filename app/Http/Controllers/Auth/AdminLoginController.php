<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __invoke(): View
    {
        return view('pages::auth.admin-login');
    }
}
