<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $type->label() }} — {{ $company->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
        .totals { margin-top: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $type->label() }}</h1>
    <div class="meta">
        {{ $company->name }} · {{ $filters['from'] }} to {{ $filters['to'] }} · Generated {{ $generatedAt->format('M j, Y g:i A') }} by {{ $generatedBy }}
    </div>

    @if($type === \App\Enums\ReportType::ExpenseRegister)
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Employee</th>
                    <th>Status</th>
                    <th class="right">Amount</th>
                    <th class="right">GST</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $expense)
                    <tr>
                        <td>{{ $expense->code }}</td>
                        <td>{{ $expense->date->format('Y-m-d') }}</td>
                        <td>{{ $expense->description }}</td>
                        <td>{{ $expense->category?->name ?? '—' }}</td>
                        <td>{{ $expense->submitter?->name ?? '—' }}</td>
                        <td>{{ $expense->status->label() }}</td>
                        <td class="right">₹{{ number_format($expense->amount, 2) }}</td>
                        <td class="right">{{ $expense->gst_amount ? '₹'.number_format($expense->gst_amount, 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    @if($type === \App\Enums\ReportType::UserSummary)
                        <th>Email</th>
                    @endif
                    <th class="right">Expenses</th>
                    <th class="right">Amount</th>
                    <th class="right">GST</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->label }}</td>
                        @if($type === \App\Enums\ReportType::UserSummary)
                            <td>{{ $row->email }}</td>
                        @endif
                        <td class="right">{{ $row->expense_count }}</td>
                        <td class="right">₹{{ number_format($row->total_amount, 2) }}</td>
                        <td class="right">₹{{ number_format($row->total_gst, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="totals">
        Total: {{ $totals['count'] }} expenses · ₹{{ number_format($totals['amount'], 2) }} · GST ₹{{ number_format($totals['gst'], 2) }}
    </div>
</body>
</html>
