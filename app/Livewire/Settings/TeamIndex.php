<?php

namespace App\Livewire\Settings;

use App\Enums\UserRole;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Team')]
class TeamIndex extends Component
{
    use WithSaveFeedback;

    public bool $showAddModal = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $role = 'employee';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('users.invite'), 403);
    }

    public function openAdd(): void
    {
        $this->reset(['name', 'email', 'phone', 'password']);
        $this->role = UserRole::Employee->value;
        $this->showAddModal = true;
    }

    public function addMember(TeamService $teamService): void
    {
        abort_unless(Auth::user()->can('users.invite'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        if (in_array($validated['role'], [UserRole::Owner->value, UserRole::SuperAdmin->value], true)) {
            $this->addError('role', 'Choose an organization role such as Admin, Manager, Accountant, or Employee.');

            return;
        }

        try {
            $teamService->addMember(Auth::user()->company, Auth::user(), $validated);
        } catch (ValidationException $e) {
            $this->addError('email', Arr::first(Arr::flatten($e->errors())) ?? $e->getMessage());

            return;
        } catch (\InvalidArgumentException $e) {
            $this->addError('email', $e->getMessage());

            return;
        }

        $this->showAddModal = false;
        $this->notifySaved('Team member added. They can sign in with the email and password you set.');
    }

    public function updateRole(int $userId, string $role, TeamService $teamService): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $member = User::query()->where('company_id', Auth::user()->company_id)->findOrFail($userId);

        try {
            $teamService->updateRole($member, UserRole::from($role), Auth::user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyFailed(collect($e->errors())->flatten()->first());

            return;
        }

        $this->notifySaved('Role updated successfully.');
    }

    public function toggleActive(int $userId, TeamService $teamService): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $member = User::query()->where('company_id', Auth::user()->company_id)->findOrFail($userId);
        $wasActive = $member->is_active;

        try {
            $teamService->setActive($member, ! $wasActive, Auth::user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyFailed(collect($e->errors())->flatten()->first());

            return;
        }

        $this->notifySaved($wasActive ? 'Member deactivated.' : 'Member reactivated.');
    }

    public function render()
    {
        $members = User::query()
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        $assignableRoles = collect(UserRole::cases())
            ->reject(fn (UserRole $r) => in_array($r, [UserRole::Owner, UserRole::SuperAdmin], true))
            ->values();

        return view('livewire.settings.team-index', [
            'members' => $members,
            'assignableRoles' => $assignableRoles,
        ]);
    }
}
