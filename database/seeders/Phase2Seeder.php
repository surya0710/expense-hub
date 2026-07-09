<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\Approval\ApprovalWorkflowService;
use Illuminate\Database\Seeder;

class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        $service = app(ApprovalWorkflowService::class);

        Company::query()->each(function (Company $company) use ($service) {
            if ($company->approvalWorkflows()->where('is_default', true)->exists()) {
                return;
            }

            $service->seedDefaultWorkflow($company);
        });
    }
}
