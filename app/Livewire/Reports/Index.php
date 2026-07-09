<?php

namespace App\Livewire\Reports;

use App\Enums\ReportType;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\User;
use App\Services\Report\ReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reports')]
class Index extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $type = 'expense_register';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $category_id = null;

    #[Url]
    public ?int $cost_center_id = null;

    #[Url]
    public ?int $submitted_by = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('expense.view.own'), 403);

        if ($this->from === '') {
            $this->from = now()->startOfMonth()->toDateString();
        }

        if ($this->to === '') {
            $this->to = now()->toDateString();
        }
    }

    public function setPreset(string $preset): void
    {
        match ($preset) {
            'this_month' => [
                $this->from = now()->startOfMonth()->toDateString(),
                $this->to = now()->toDateString(),
            ],
            'last_month' => [
                $this->from = now()->subMonth()->startOfMonth()->toDateString(),
                $this->to = now()->subMonth()->endOfMonth()->toDateString(),
            ],
            'this_quarter' => [
                $this->from = now()->firstOfQuarter()->toDateString(),
                $this->to = now()->toDateString(),
            ],
            default => null,
        };
    }

    protected function filters(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status ?: null,
            'category_id' => $this->category_id,
            'cost_center_id' => $this->cost_center_id,
            'submitted_by' => $this->submitted_by,
        ];
    }

    public function exportQuery(): string
    {
        return http_build_query([
            'type' => $this->type,
            ...$this->filters(),
        ]);
    }

    public function render(ReportService $reportService)
    {
        $user = Auth::user();
        $reportType = ReportType::from($this->type);

        if ($reportType->requiresCompanyView()) {
            abort_unless($user->can('expense.view.all'), 403);
        }

        $filters = $this->filters();
        $rows = $reportService->run($user, $reportType, $filters);
        $totals = $reportService->totals($user, $filters);

        $availableTypes = collect(ReportType::cases())
            ->reject(fn (ReportType $type) => $type->requiresCompanyView() && ! $user->can('expense.view.all'));

        return view('livewire.reports.index', [
            'rows' => $rows,
            'totals' => $totals,
            'reportType' => $reportType,
            'exportQuery' => ['type' => $this->type, ...$filters],
            'availableTypes' => $availableTypes,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->where('is_active', true)->orderBy('name')->get(),
            'employees' => $user->can('expense.view.all')
                ? User::query()
                    ->where('company_id', $user->company_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                : collect(),
            'statuses' => $reportService->filterableStatuses(),
            'canViewAllExpenses' => $user->can('expense.view.all'),
        ]);
    }
}
