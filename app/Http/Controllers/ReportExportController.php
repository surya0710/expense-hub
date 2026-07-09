<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $request, ReportService $reportService): StreamedResponse
    {
        abort_unless(Auth::user()->can('report.export.excel'), 403);

        $filters = $this->validatedFilters($request);
        $type = ReportType::from($request->string('type')->toString());
        $user = Auth::user();

        if ($type->requiresCompanyView()) {
            abort_unless($user->can('expense.view.all'), 403);
        }

        $rows = $reportService->run($user, $type, $filters);
        $filename = $type->value.'_'.$filters['from'].'_'.$filters['to'].'.csv';

        return response()->streamDownload(function () use ($type, $rows, $filters, $reportService, $user) {
            $handle = fopen('php://output', 'w');

            foreach ($this->csvRows($type, $rows, $filters, $reportService, $user) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function pdf(Request $request, ReportService $reportService)
    {
        abort_unless(Auth::user()->can('report.export.pdf'), 403);

        $filters = $this->validatedFilters($request);
        $type = ReportType::from($request->string('type')->toString());
        $user = Auth::user();

        if ($type->requiresCompanyView()) {
            abort_unless($user->can('expense.view.all'), 403);
        }

        $rows = $reportService->run($user, $type, $filters);
        $totals = $reportService->totals($user, $filters);

        $pdf = Pdf::loadView('exports.report-pdf', [
            'company' => $user->company,
            'type' => $type,
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $totals,
            'generatedAt' => now(),
            'generatedBy' => $user->name,
        ])->setPaper('a4', $type === ReportType::ExpenseRegister ? 'landscape' : 'portrait');

        return $pdf->download($type->value.'_'.$filters['from'].'_'.$filters['to'].'.pdf');
    }

    /**
     * @return array{from: string, to: string, status?: string|null, category_id?: int|null, cost_center_id?: int|null, submitted_by?: int|null}
     */
    protected function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'submitted_by' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    protected function csvRows(ReportType $type, $rows, array $filters, ReportService $reportService, $user): \Generator
    {
        if ($type === ReportType::ExpenseRegister) {
            yield ['Code', 'Date', 'Description', 'Category', 'Cost Center', 'Employee', 'Vendor', 'Payment', 'Status', 'Amount', 'GST'];

            foreach ($rows as $expense) {
                yield [
                    $expense->code,
                    $expense->date->format('Y-m-d'),
                    $expense->description,
                    $expense->category?->name,
                    $expense->costCenter?->name,
                    $expense->submitter?->name,
                    $expense->vendor_name,
                    $expense->payment_mode->label(),
                    $expense->status->label(),
                    $expense->amount,
                    $expense->gst_amount,
                ];
            }

            $totals = $reportService->totals($user, $filters);
            yield [];
            yield ['Total', '', '', '', '', '', '', '', $totals['count'].' expenses', $totals['amount'], $totals['gst']];

            return;
        }

        $headers = match ($type) {
            ReportType::UserSummary => ['Employee', 'Email', 'Expenses', 'Total amount', 'GST'],
            ReportType::GstSummary => ['GST rate', 'Expenses', 'Taxable amount', 'GST amount'],
            default => ['Name', 'Expenses', 'Total amount', 'GST'],
        };

        yield $headers;

        foreach ($rows as $row) {
            yield match ($type) {
                ReportType::UserSummary => [
                    $row->label,
                    $row->email,
                    $row->expense_count,
                    $row->total_amount,
                    $row->total_gst,
                ],
                ReportType::GstSummary => [
                    $row->label,
                    $row->expense_count,
                    $row->total_amount,
                    $row->total_gst,
                ],
                default => [
                    $row->label,
                    $row->expense_count,
                    $row->total_amount,
                    $row->total_gst,
                ],
            };
        }
    }
}
