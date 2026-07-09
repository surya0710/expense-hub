<?php

namespace Database\Seeders;

use App\Enums\Industry;
use App\Models\Company;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CostCenterTemplateSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    protected array $templates = [
        'retail' => ['Head office', 'Store / Branch', 'Warehouse', 'E-commerce', 'Admin'],
        'manufacturing' => ['Head office', 'Plant / Factory', 'Warehouse', 'Site operations', 'Admin'],
        'services' => ['Head office', 'Sales', 'Delivery / Projects', 'Remote / WFH', 'Admin'],
        'hospitality' => ['Head office', 'Outlet / Property', 'Kitchen', 'Housekeeping', 'Admin'],
        'healthcare' => ['Head office', 'Clinic / Hospital', 'Pharmacy', 'Diagnostics', 'Admin'],
        'other' => ['Head office', 'Sales', 'Operations', 'Admin', 'General'],
    ];

    public function run(): void
    {
        Company::query()->each(function (Company $company) {
            if ($company->costCenters()->exists()) {
                return;
            }

            $this->seedForCompany($company, $company->industry);
        });
    }

    public function seedForCompany(Company $company, Industry $industry): void
    {
        $key = $industry === Industry::Other ? 'other' : $industry->value;
        $names = $this->templates[$key] ?? $this->templates['other'];

        foreach ($names as $name) {
            CostCenter::query()->create([
                'company_id' => $company->id,
                'name' => $name,
                'code' => Str::upper(Str::slug($name, '_')),
                'is_active' => true,
            ]);
        }
    }
}
