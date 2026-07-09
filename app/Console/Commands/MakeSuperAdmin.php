<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MakeSuperAdmin extends Command
{
    protected $signature = 'app:make-super-admin {email : Existing user email address}';

    protected $description = 'Promote an existing user account to platform super admin.';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email address.');

            return self::FAILURE;
        }

        foreach (['platform.view', 'platform.subscriptions.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $role->syncPermissions(['platform.view', 'platform.subscriptions.manage']);

        $user->assignRole(UserRole::SuperAdmin->value);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info($user->email.' is now a super admin.');

        return self::SUCCESS;
    }
}
