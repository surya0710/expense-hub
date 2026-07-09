<?php

namespace App\Services\Company;

use App\Enums\CompanyStatus;
use App\Enums\Industry;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Support\Organization\EmailDomain;
use Database\Seeders\CategoryTemplateSeeder;
use Database\Seeders\CostCenterTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyRegistrationService
{
    public function companyForEmail(string $email): ?Company
    {
        if (! EmailDomain::isEligibleForAutoJoin($email)) {
            return null;
        }

        $domain = EmailDomain::fromEmail($email);

        return Company::query()
            ->where('domain', $domain)
            ->where('domain_auto_join', true)
            ->first();
    }

    /**
     * @param  array{name: string, email: string, password: string, phone?: string, company_name?: string, industry?: Industry|string}  $data
     */
    public function register(array $data): User
    {
        $existing = $this->companyForEmail($data['email']);

        if ($existing) {
            return $this->joinCompany($existing, $data);
        }

        if (empty($data['company_name']) || empty($data['industry'])) {
            throw ValidationException::withMessages([
                'company_name' => 'Company name is required when creating a new organization.',
            ]);
        }

        return $this->createOrganization($data);
    }

    /**
     * @param  array{google_id: string, name: string, email: string, avatar?: string, company_name?: string, industry?: Industry|string}  $data
     */
    public function registerFromOAuth(array $data): User
    {
        $existing = $this->companyForEmail($data['email']);

        if ($existing) {
            return $this->joinCompanyFromOAuth($existing, $data);
        }

        if (empty($data['company_name']) || empty($data['industry'])) {
            throw ValidationException::withMessages([
                'company_name' => 'Company name is required when creating a new organization.',
            ]);
        }

        return $this->createOrganizationFromOAuth($data);
    }

    /**
     * @param  array{name: string, email: string, password: string, phone?: string}  $data
     */
    public function joinCompany(Company $company, array $data): User
    {
        return DB::transaction(function () use ($company, $data) {
            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            $user->assignRole(config('organization.domain_join_role', UserRole::Employee->value));

            return $user;
        });
    }

    /**
     * @param  array{google_id: string, name: string, email: string, avatar?: string}  $data
     */
    public function joinCompanyFromOAuth(Company $company, array $data): User
    {
        return DB::transaction(function () use ($company, $data) {
            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'google_id' => $data['google_id'],
                'avatar_url' => $data['avatar'] ?? null,
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]);

            $user->assignRole(config('organization.domain_join_role', UserRole::Employee->value));

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, password: string, phone?: string, company_name: string, industry: Industry|string}  $data
     */
    protected function createOrganization(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $industry = $data['industry'] instanceof Industry
                ? $data['industry']
                : Industry::from($data['industry']);

            $company = Company::query()->create([
                ...$this->domainAttributesForEmail($data['email']),
                'name' => $data['company_name'],
                'slug' => $this->uniqueSlug($data['company_name']),
                'industry' => $industry,
                'currency' => 'INR',
                'country' => 'IN',
                'status' => CompanyStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::Owner->value);

            app(CategoryTemplateSeeder::class)->seedForCompany($company, $industry);
            app(CostCenterTemplateSeeder::class)->seedForCompany($company, $industry);
            app(\App\Services\Approval\ApprovalWorkflowService::class)->seedDefaultWorkflow($company);

            $company->update(['settings' => ['onboarding_completed' => false]]);

            return $user;
        });
    }

    /**
     * @param  array{google_id: string, name: string, email: string, avatar?: string, company_name: string, industry: Industry|string}  $data
     */
    protected function createOrganizationFromOAuth(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $industry = $data['industry'] instanceof Industry
                ? $data['industry']
                : Industry::from($data['industry']);

            $company = Company::query()->create([
                ...$this->domainAttributesForEmail($data['email']),
                'name' => $data['company_name'],
                'slug' => $this->uniqueSlug($data['company_name']),
                'industry' => $industry,
                'currency' => 'INR',
                'country' => 'IN',
                'status' => CompanyStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'google_id' => $data['google_id'],
                'avatar_url' => $data['avatar'] ?? null,
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::Owner->value);

            app(CategoryTemplateSeeder::class)->seedForCompany($company, $industry);
            app(CostCenterTemplateSeeder::class)->seedForCompany($company, $industry);
            app(\App\Services\Approval\ApprovalWorkflowService::class)->seedDefaultWorkflow($company);

            $company->update(['settings' => ['onboarding_completed' => false]]);

            return $user;
        });
    }

    /**
     * @return array{domain: ?string, domain_auto_join: bool}
     */
    protected function domainAttributesForEmail(string $email): array
    {
        if (! EmailDomain::isEligibleForAutoJoin($email)) {
            return ['domain' => null, 'domain_auto_join' => false];
        }

        $domain = EmailDomain::fromEmail($email);

        if (Company::query()->where('domain', $domain)->exists()) {
            return ['domain' => null, 'domain_auto_join' => false];
        }

        return [
            'domain' => $domain,
            'domain_auto_join' => true,
        ];
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
