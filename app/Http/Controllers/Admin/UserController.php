<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** User administration (routes are limited to admins). */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['tickets', 'assignedTickets as open_assigned_count' => fn ($q) => $q->active()])
            ->when($request->query('q'), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")))
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::cases()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => Role::Employee, 'is_active' => true]), 'roles' => Role::cases()]);
    }

    /** New accounts get a random password and an email link to choose their own. */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::create($data + ['password' => Str::password(32), 'email_verified_at' => now()]);
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.index')
            ->with('status', "{$user->name} was added and has been emailed a link to set their password.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        // Admins can't lock themselves out by demoting or deactivating their own account.
        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! $data['is_active'])) {
            return back()->withErrors(['role' => "You can't remove your own admin access or deactivate yourself."]);
        }

        $user->update($data);

        $message = "{$user->name} updated.";

        // Leaver process: hand their open tickets back to the queue.
        if (! $user->is_active || ! $user->isStaff()) {
            $returned = $user->assignedTickets()->active()->get()->each(function ($ticket) {
                $ticket->update([
                    'assignee_id' => null,
                    'status' => $ticket->status === TicketStatus::InProgress ? TicketStatus::Open : $ticket->status,
                ]);
            })->count();

            if ($returned) {
                $message .= " {$returned} open ".Str::plural('ticket', $returned).' returned to the queue.';
            }
        }

        return redirect()->route('admin.users.index')->with('status', $message);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            'department' => ['nullable', 'string', 'max:100'],
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
