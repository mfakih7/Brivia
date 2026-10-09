<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\InvitationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationAcceptanceController extends Controller
{
    public function __construct(private readonly InvitationManager $invitations) {}

    public function show(string $token): View
    {
        $invitation = $this->invitations->findPending($token);

        return view('admin.auth.accept-invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $this->invitations->accept($token, trim($data['name']), $data['password']);

        Auth::guard('web')->logout();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Welcome! Your account is ready.');
    }
}
