<?php

namespace App\Livewire\AuditLog;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.app')]
#[Title('Audit log')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $event = '';

    #[Url]
    public ?int $causer_id = null;

    #[Url]
    public string $search = '';

    public ?int $viewingActivityId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('audit.view'), 403);

        if ($this->from === '') {
            $this->from = now()->subDays(30)->toDateString();
        }

        if ($this->to === '') {
            $this->to = now()->toDateString();
        }
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedCauserId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function viewActivity(int $id): void
    {
        $this->viewingActivityId = $id;
    }

    public function closeActivity(): void
    {
        $this->viewingActivityId = null;
    }

    public function render(AuditLogService $auditLogService)
    {
        $user = Auth::user();
        $company = $user->company;

        $activities = $auditLogService
            ->query($company, [
                'from' => $this->from,
                'to' => $this->to,
                'event' => $this->event ?: null,
                'causer_id' => $this->causer_id,
                'search' => $this->search ?: null,
            ])
            ->paginate(25);

        $viewingActivity = $this->viewingActivityId
            ? Activity::query()->with(['causer', 'subject'])->find($this->viewingActivityId)
            : null;

        if ($viewingActivity && ! $auditLogService->belongsToCompany($viewingActivity, $company->id)) {
            $viewingActivity = null;
            $this->viewingActivityId = null;
        }

        return view('livewire.audit-log.index', [
            'activities' => $activities,
            'teamMembers' => User::query()
                ->where('company_id', $user->company_id)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'viewingActivity' => $viewingActivity,
            'viewingChanges' => $viewingActivity ? $auditLogService->formattedChanges($viewingActivity) : collect(),
            'auditLogService' => $auditLogService,
        ]);
    }
}
