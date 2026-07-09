<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    protected array $permissions = [
        'expense.create.own',
        'expense.view.own',
        'expense.view.all',
        'expense.approve',
        'expense.reject',
        'expense.delete.any',
        'receipt.download',
        'wallet.view',
        'wallet.manage',
        'report.export.pdf',
        'report.export.excel',
        'budget.manage',
        'budget.view',
        'reimbursement.view',
        'reimbursement.manage',
        'audit.view',
        'audit.export',
        'settings.manage',
        'users.invite',
        'subscription.manage',
        'platform.view',
        'platform.subscriptions.manage',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = Permission::all();

        $rolePermissions = [
            UserRole::SuperAdmin->value => [
                'platform.view',
                'platform.subscriptions.manage',
            ],
            UserRole::Owner->value => $all
                ->whereNotIn('name', ['platform.view', 'platform.subscriptions.manage'])
                ->pluck('name')
                ->all(),
            UserRole::Admin->value => $all
                ->whereNotIn('name', ['subscription.manage', 'platform.view', 'platform.subscriptions.manage'])
                ->pluck('name')
                ->all(),
            UserRole::Manager->value => [
                'expense.create.own', 'expense.view.own', 'expense.view.all',
                'expense.approve', 'expense.reject', 'receipt.download',
                'wallet.view', 'budget.view',
            ],
            UserRole::Accountant->value => [
                'expense.view.all', 'receipt.download', 'wallet.view',
                'report.export.pdf', 'report.export.excel', 'budget.view',
                'reimbursement.view', 'reimbursement.manage',
            ],
            UserRole::Employee->value => [
                'expense.create.own', 'expense.view.own', 'receipt.download',
                'budget.view', 'reimbursement.view',
            ],
            UserRole::Auditor->value => [
                'expense.view.all', 'receipt.download',
                'report.export.pdf', 'report.export.excel', 'budget.view',
                'audit.view', 'audit.export',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
