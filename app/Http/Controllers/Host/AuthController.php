<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Http\Requests\Host\HostLoginRequest;
use App\Services\HostAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('host.login');
    }

    public function store(HostLoginRequest $request, HostAuthService $auth)
    {
        $request->authenticate($auth);

        return redirect()->intended(route('host.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('host')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('host.login')->with('success', 'Anda telah keluar.');
    }
}
