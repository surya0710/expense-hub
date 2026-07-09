<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogExportController extends Controller
{
    public function csv(Request $request, AuditLogService $auditLogService): StreamedResponse
    {
        abort_unless(Auth::user()->can('audit.export'), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'event' => ['nullable', 'string', 'in:created,updated,deleted'],
            'causer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $company = Auth::user()->company;
        $activities = $auditLogService
            ->query($company, $validated)
            ->limit(5000)
            ->get();

        $filename = 'audit_log_'.$company->id.'_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($activities, $auditLogService) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['When', 'User', 'Event', 'Entity type', 'Entity', 'Summary']);

            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->created_at->toDateTimeString(),
                    $activity->causer?->name ?? 'System',
                    $auditLogService->eventLabel($activity),
                    $auditLogService->subjectTypeLabel($activity),
                    $auditLogService->subjectLabel($activity),
                    $auditLogService->summary($activity),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
