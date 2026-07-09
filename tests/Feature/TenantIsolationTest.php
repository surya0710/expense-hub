<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\ExpenseStatus;
use App\Enums\PayoutBatchStatus;
use App\Livewire\Settings\BudgetIndex;
use App\Models\Category;
use App\Models\Company;
use App\Models\Expense;
use App\Models\PayoutBatch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_tenant_pages_stay_scoped_to_their_organization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Pvt Ltd', 'alpha-owner@example.com');
        [$companyB, $ownerB] = $this->companyWithOwner('Beta Pvt Ltd', 'beta-owner@example.com');

        $ownerA->assignRole('super_admin');

        $this->payoutBatch($companyA, $ownerA, 'PAY-ALPHA-001');
        $this->payoutBatch($companyB, $ownerB, 'PAY-BETA-001');

        $this->actingAs($ownerA)
            ->get(route('reimbursements.index', ['tab' => 'batches']))
            ->assertOk()
            ->assertSee('PAY-ALPHA-001')
            ->assertDontSee('PAY-BETA-001');
    }

    public function test_super_admin_dashboard_can_show_all_organizations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Pvt Ltd', 'alpha-owner@example.com');
        $this->companyWithOwner('Beta Pvt Ltd', 'beta-owner@example.com');

        $ownerA->assignRole('super_admin');

        $this->actingAs($ownerA)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Alpha Pvt Ltd')
            ->assertSee('Beta Pvt Ltd')
            ->assertSee('alpha-owner@example.com')
            ->assertSee('beta-owner@example.com');
    }

    public function test_reports_employee_filter_only_lists_current_company_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Pvt Ltd', 'alpha-owner@example.com');
        $this->userForCompany($companyA, 'Alpha Employee', 'alpha-employee@example.com');
        $this->companyWithOwner('Beta Pvt Ltd', 'beta-owner@example.com');

        $this->actingAs($ownerA)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Alpha Employee')
            ->assertDontSee('Beta Pvt Ltd Owner');
    }

    public function test_budget_user_scope_rejects_users_from_other_companies(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Pvt Ltd', 'alpha-owner@example.com');
        [, $ownerB] = $this->companyWithOwner('Beta Pvt Ltd', 'beta-owner@example.com');

        Livewire::actingAs($ownerA)
            ->test(BudgetIndex::class)
            ->set('name', 'Cross-company user budget')
            ->set('scope', 'user')
            ->set('user_id', $ownerB->id)
            ->set('amount', '1000')
            ->set('period', 'monthly')
            ->set('alert_percent', 80)
            ->set('block_at_limit', false)
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['user_id']);
    }

    public function test_expense_form_rejects_categories_from_other_companies(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [, $ownerA] = $this->companyWithOwner('Alpha Pvt Ltd', 'alpha-owner@example.com');
        [$companyB] = $this->companyWithOwner('Beta Pvt Ltd', 'beta-owner@example.com');

        $foreignCategory = Category::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Beta Travel',
            'code' => 'BETA_TRAVEL',
            'is_active' => true,
        ]);

        $validator = validator([
            'category_id' => $foreignCategory->id,
        ], [
            'category_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('categories', 'id')->where('company_id', $ownerA->company_id),
            ],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_suspended_organization_users_are_blocked_from_tenant_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$company, $owner] = $this->companyWithOwner('Suspended Pvt Ltd', 'suspended-owner@example.com');
        $company->update(['status' => CompanyStatus::Suspended]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @return array{Company, User}
     */
    protected function companyWithOwner(string $companyName, string $email): array
    {
        $company = Company::query()->create([
            'name' => $companyName,
            'slug' => str($companyName)->slug()->toString(),
            'industry' => 'services',
            'currency' => 'INR',
            'country' => 'IN',
            'status' => CompanyStatus::Active,
            'plan' => 'free',
        ]);

        $owner = $this->userForCompany($company, $companyName.' Owner', $email);
        $owner->assignRole('owner');

        return [$company, $owner];
    }

    protected function userForCompany(Company $company, string $name, string $email): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    protected function payoutBatch(Company $company, User $owner, string $reference): void
    {
        $expense = Expense::query()->create([
            'company_id' => $company->id,
            'code' => $reference.'-EXP',
            'submitted_by' => $owner->id,
            'date' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'INR',
            'payment_mode' => 'upi',
            'description' => $reference.' expense',
            'reimbursable' => true,
            'status' => ExpenseStatus::ReimbursementPending,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $batch = PayoutBatch::query()->create([
            'company_id' => $company->id,
            'reference' => $reference,
            'status' => PayoutBatchStatus::Pending,
            'total_amount' => 1000,
            'created_by' => $owner->id,
        ]);

        $expense->update(['payout_batch_id' => $batch->id]);
    }
}
