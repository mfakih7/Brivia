<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\StaffAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.account.security', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request, StaffAccessManager $access, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('current_password'), $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $user->forceFill(['password' => $request->string('password')])->save();
        $access->revokeSessions($user, $request->session()->getId());
        $request->session()->regenerate();
        $audit->record('security.password_changed', 'user', $user->id);

        return back()->with('status', 'Password updated. Other sessions have been signed out.');
    }
}
