<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AdminInvitation;
use App\Models\User;
use App\Support\InvitationManager;
use App\Support\StaffAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly StaffAccessManager $access) {}

    public function index(): View
    {
        return view('admin.staff.index', [
            'users' => User::orderByDesc('is_active')->orderBy('name')->get(),
            'invitations' => AdminInvitation::with('inviter')->latest('id')->limit(30)->get(),
            'roles' => Role::cases(),
        ]);
    }

    public function invite(Request $request, InvitationManager $invitations): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $invitations->invite($request->user(), $data['email'], Role::from($data['role']));

        return back()->with('status', 'Invitation queued. It expires in 48 hours and works once.');
    }

    public function revokeInvitation(Request $request, AdminInvitation $invitation, InvitationManager $invitations): RedirectResponse
    {
        $invitations->revoke($request->user(), $invitation);

        return back()->with('status', 'Invitation revoked.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        $this->access->changeRole($request->user(), $user, Role::from($data['role']));

        return back()->with('status', "Role updated for {$user->name}.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account. Ask another owner.');
        }

        $this->access->deactivate($request->user(), $user);

        return back()->with('status', "{$user->name} has been deactivated and signed out.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->access->reactivate($request->user(), $user);

        return back()->with('status', "{$user->name} has been reactivated.");
    }
}
