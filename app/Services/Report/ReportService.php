<?php

namespace App\Services\Report;

use App\Enums\ExpenseStatus;
use App\Enums\ReportType;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @param  array{
     *     from: string,
     *     to: string,
     *     status?: string|null,
     *     category_id?: int|null,
     *     cost_center_id?: int|null,
     *     submitted_by?: int|null,
     * }  $filters
     */
    public function baseQuery(User $user, array $filters): Builder
    {
        $query = Expense::query()
            ->visibleToUser($user)
            ->with(['category', 'costCenter', 'submitter'])
            ->whereBetween('date', [$filters['from'], $filters['to']])
            ->orderBy('date')
            ->orderBy('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['cost_center_id'])) {
            $query->where('cost_center_id', $filters['cost_center_id']);
        }

        if (! empty($filters['submitted_by']) && $user->can('expense.view.all')) {
            $query->where('submitted_by', $filters['submitted_by']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, mixed>|Collection<int, Expense>
     */
    public function run(User $user, ReportType $type, array $filters): Collection
    {
        return match ($type) {
            ReportType::ExpenseRegister => $this->baseQuery($user, $filters)->get(),
            ReportType::CategorySummary => $this->categorySummary($user, $filters),
            ReportType::CostCenterSummary => $this->costCenterSummary($user, $filters),
            ReportType::UserSummary => $this->userSummary($user, $filters),
            ReportType::GstSummary => $this->gstSummary($user, $filters),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function totals(User $user, array $filters): array
    {
        $query = $this->baseQuery($user, $filters);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
            'gst' => (float) (clone $query)->sum('gst_amount'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function categorySummary(User $user, array $filters): Collection
    {
        return $this->aggregateQuery($user, $filters)
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->select(
                'categories.name as label',
                'categories.color as color',
                DB::raw('COUNT(expenses.id) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(COALESCE(expenses.gst_amount, 0)) as total_gst'),
            )
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function costCenterSummary(User $user, array $filters): Collection
    {
        return $this->aggregateQuery($user, $filters)
            ->leftJoin('cost_centers', 'expenses.cost_center_id', '=', 'cost_centers.id')
            ->groupBy('cost_centers.id', 'cost_centers.name')
            ->select(
                DB::raw("COALESCE(cost_centers.name, 'Unassigned') as label"),
                DB::raw('COUNT(expenses.id) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(COALESCE(expenses.gst_amount, 0)) as total_gst'),
            )
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function userSummary(User $user, array $filters): Collection
    {
        abort_unless($user->can('expense.view.all'), 403);

        return $this->aggregateQuery($user, $filters)
            ->join('users', 'expenses.submitted_by', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->select(
                'users.name as label',
                'users.email as email',
                DB::raw('COUNT(expenses.id) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(COALESCE(expenses.gst_amount, 0)) as total_gst'),
            )
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function gstSummary(User $user, array $filters): Collection
    {
        return $this->aggregateQuery($user, $filters)
            ->whereNotNull('expenses.gst_amount')
            ->where('expenses.gst_amount', '>', 0)
            ->groupBy('expenses.gst_percent')
            ->select(
                'expenses.gst_percent as label',
                DB::raw('COUNT(expenses.id) as expense_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                DB::raw('SUM(expenses.gst_amount) as total_gst'),
            )
            ->orderBy('expenses.gst_percent')
            ->get()
            ->map(function ($row) {
                $row->label = $row->label.'% GST';

                return $row;
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function aggregateQuery(User $user, array $filters): Builder
    {
        $query = Expense::query()
            ->visibleToUser($user)
            ->whereBetween('expenses.date', [$filters['from'], $filters['to']]);

        if (! empty($filters['status'])) {
            $query->where('expenses.status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('expenses.category_id', $filters['category_id']);
        }

        if (! empty($filters['cost_center_id'])) {
            $query->where('expenses.cost_center_id', $filters['cost_center_id']);
        }

        if (! empty($filters['submitted_by']) && $user->can('expense.view.all')) {
            $query->where('expenses.submitted_by', $filters['submitted_by']);
        }

        return $query;
    }

    /**
     * @return list<ExpenseStatus>
     */
    public function filterableStatuses(): array
    {
        return ExpenseStatus::cases();
    }
}
