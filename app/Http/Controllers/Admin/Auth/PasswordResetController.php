<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\StaffAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    /** Always returns the same generic response so account existence is not revealed. */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:254']]);

        Password::sendResetLink(['email' => Str::lower($request->string('email')), 'is_active' => true]);

        return back()->with('status', 'If an active staff account exists for that address, a reset link has been sent.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request, StaffAccessManager $access, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            ['email' => Str::lower($request->string('email')), 'is_active' => true] + $request->only('password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($access, $audit) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $access->revokeSessions($user);
                $audit->record('security.password_reset', 'user', $user->id, [], $user->id);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or has expired. Request a new one.']);
        }

        return redirect()->route('admin.login')->with('status', 'Your password has been reset. Please sign in.');
    }
}
