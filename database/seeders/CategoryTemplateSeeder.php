<?php

namespace Database\Seeders;

use App\Enums\Industry;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CategoryTemplateSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    protected array $templates = [
        'retail' => [
            'Rent', 'Salaries', 'Electricity', 'Water', 'Internet', 'Packaging',
            'Stationery', 'Cleaning', 'Marketing', 'Repairs', 'Travel', 'Food',
            'POS charges', 'Bank charges', 'Misc',
        ],
        'manufacturing' => [
            'Raw material', 'Labour wages', 'Freight-in', 'Freight-out', 'Machinery repair',
            'Fuel/Diesel', 'Power', 'Consumables', 'Safety gear', 'R&D', 'Statutory fees',
            'Site petty cash', 'Canteen', 'Security', 'Misc',
        ],
        'services' => [
            'Salaries', 'Contractor fees', 'Software subscriptions', 'Cloud hosting',
            'Office rent', 'Client meetings', 'Travel', 'Marketing/Ads', 'Training',
            'Legal & professional', 'Bank charges', 'Refreshments', 'Misc',
        ],
        'hospitality' => [
            'Groceries', 'Beverages', 'Kitchen fuel/gas', 'Cleaning', 'Laundry',
            'Staff meals', 'Repairs', 'Utilities', 'Licenses', 'Marketing', 'Petty tips', 'Misc',
        ],
        'healthcare' => [
            'Medicines', 'Consumables', 'Equipment maintenance', 'Housekeeping', 'Laundry',
            'Staff meals', 'Utilities', 'Bio-medical waste', 'Licenses', 'Software', 'Misc',
        ],
        'other' => [
            'Office supplies', 'Travel', 'Meals', 'Utilities', 'Rent', 'Salaries', 'Misc',
        ],
    ];

    /** @var list<string> */
    protected array $colors = [
        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316',
        '#eab308', '#22c55e', '#14b8a6', '#0ea5e9', '#64748b',
    ];

    public function run(): void
    {
        // Called from DatabaseSeeder only for demo data if needed.
    }

    public function seedForCompany(Company $company, Industry $industry): void
    {
        $key = $industry === Industry::Other ? 'other' : $industry->value;
        $names = $this->templates[$key] ?? $this->templates['other'];

        foreach ($names as $index => $name) {
            Category::query()->create([
                'company_id' => $company->id,
                'name' => $name,
                'color' => $this->colors[$index % count($this->colors)],
                'is_active' => true,
            ]);
        }
    }
}
