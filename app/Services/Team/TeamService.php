<?php

namespace App\Services\Team;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use App\Support\Organization\EmailDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, role: UserRole|string, phone?: string}  $data
     */
    public function addMember(Company $company, User $invitedBy, array $data): User
    {
        $role = $data['role'] instanceof UserRole
            ? $data['role']
            : UserRole::from($data['role']);

        if ($role === UserRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'There can only be one owner per organization. Use Admin or Manager instead.',
            ]);
        }

        return DB::transaction(function () use ($company, $data, $role) {
            $this->subscriptionService->assertCanAddUser($company);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $user->assignRole($role->value);

            return $user;
        });
    }

    public function updateRole(User $member, UserRole $role, User $actor): void
    {
        if ($member->company_id !== $actor->company_id) {
            abort(403);
        }

        if ($member->is($actor)) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role.',
            ]);
        }

        if ($member->hasRole(UserRole::Owner->value) && $role !== UserRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'The organization owner role cannot be changed.',
            ]);
        }

        if ($role === UserRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'There can only be one owner per organization.',
            ]);
        }

        $previousRole = $member->getRoleNames()->first();

        $member->syncRoles([$role->value]);

        if ($previousRole !== $role->value) {
            activity()
                ->performedOn($member)
                ->causedBy($actor)
                ->event('updated')
                ->withProperties([
                    'old' => ['role' => $previousRole],
                    'attributes' => ['role' => $role->value],
                ])
                ->log('Team member role updated');
        }
    }

    public function setActive(User $member, bool $active, User $actor): void
    {
        if ($member->company_id !== $actor->company_id) {
            abort(403);
        }

        if ($member->is($actor)) {
            throw ValidationException::withMessages([
                'active' => 'You cannot deactivate your own account.',
            ]);
        }

        if ($member->hasRole(UserRole::Owner->value)) {
            throw ValidationException::withMessages([
                'active' => 'The organization owner cannot be deactivated.',
            ]);
        }

        $member->update(['is_active' => $active]);
    }

    /**
     * @param  array{name?: string, gstin?: string, currency?: string, fy_start_month?: int}  $data
     */
    public function updateCompany(Company $company, array $data): Company
    {
        $company->update([
            'name' => $data['name'],
            'gstin' => ! empty($data['gstin']) ? $data['gstin'] : null,
            'currency' => $data['currency'] ?? $company->currency,
            'fy_start_month' => $data['fy_start_month'] ?? $company->fy_start_month,
        ]);

        return $company->fresh();
    }

    /**
     * @param  array{domain?: ?string, domain_auto_join?: bool}  $data
     */
    public function updateDomainSettings(Company $company, array $data): Company
    {
        $updates = [];

        if (array_key_exists('domain_auto_join', $data)) {
            $updates['domain_auto_join'] = (bool) $data['domain_auto_join'];
        }

        if (array_key_exists('domain', $data) && $data['domain'] !== null) {
            $domain = EmailDomain::normalize($data['domain']);

            if (! $domain || EmailDomain::isGeneric($domain)) {
                throw ValidationException::withMessages([
                    'domain' => 'Use your company work domain (e.g. xyz.com). Personal email domains are not allowed.',
                ]);
            }

            if (Company::query()->where('domain', $domain)->where('id', '!=', $company->id)->exists()) {
                throw ValidationException::withMessages([
                    'domain' => 'This domain is already registered to another organization.',
                ]);
            }

            $updates['domain'] = $domain;
        }

        if ($updates !== []) {
            $company->update($updates);
        }

        return $company->fresh();
    }
}
