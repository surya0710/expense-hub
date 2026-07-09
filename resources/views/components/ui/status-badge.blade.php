@props(['status'])

@php
    $map = [
        'draft' => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
        'pending_approval' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'approved' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'reimbursement_pending' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'reimbursed' => 'bg-violet-50 text-violet-800 ring-violet-200',
    ];
    $value = $status instanceof \App\Enums\ExpenseStatus ? $status->value : $status;
    $label = $status instanceof \App\Enums\ExpenseStatus ? $status->label() : ucfirst(str_replace('_', ' ', $value));
    $classes = $map[$value] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.$classes]) }}>
    {{ $label }}
</span>
