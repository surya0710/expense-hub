<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'ExpenseHub') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased" x-data>
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-slate-900 lg:flex">
            <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-6">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <a href="{{ route('dashboard') }}" wire:navigate class="text-lg font-bold text-white">ExpenseHub</a>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z'],
                        ['route' => 'expenses.index', 'label' => 'Expenses', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ];
                    if (auth()->user()->can('wallet.view')) {
                        $navItems[] = ['route' => 'petty-cash.index', 'label' => 'Petty Cash', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'];
                    }
                    if (auth()->user()->can('expense.approve')) {
                        $navItems[] = ['route' => 'approvals.index', 'label' => 'Approvals', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'];
                    }
                    if (auth()->user()->can('reimbursement.view')) {
                        $navItems[] = ['route' => 'reimbursements.index', 'label' => 'Reimbursements', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'];
                    }
                    if (auth()->user()->can('users.invite')) {
                        $navItems[] = ['route' => 'settings.team', 'label' => 'Team', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
                    }
                    if (auth()->user()->can('settings.manage')) {
                        $navItems[] = ['route' => 'settings.company', 'label' => 'Organization', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'];
                        $navItems[] = ['route' => 'settings.categories', 'label' => 'Categories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'];
                        $navItems[] = ['route' => 'settings.approval-workflow', 'label' => 'Workflow', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                    }
                    if (auth()->user()->can('expense.view.own')) {
                        $navItems[] = ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
                    }
                    if (auth()->user()->can('audit.view')) {
                        $navItems[] = ['route' => 'audit-log.index', 'label' => 'Audit log', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'];
                    }
                    if (auth()->user()->can('budget.view') || auth()->user()->can('budget.manage')) {
                        $navItems[] = ['route' => 'settings.budgets', 'label' => 'Budgets', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                    }
                    if (auth()->user()->can('subscription.manage')) {
                        $navItems[] = ['route' => 'settings.subscription', 'label' => 'Subscription', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'];
                    }
                @endphp

                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                        @class([
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                            'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-400' => request()->routeIs($item['route'].'*') || request()->routeIs($item['route']),
                            'text-slate-400 hover:bg-slate-800 hover:text-white' => ! (request()->routeIs($item['route'].'*') || request()->routeIs($item['route'])),
                        ])>
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                        @if($item['route'] === 'reimbursements.index')
                            @php $reimbNav = app(\App\Services\Reimbursement\ReimbursementService::class)->countPendingForUser(auth()->user()); @endphp
                            @if($reimbNav > 0)
                                <span class="ml-auto rounded-full bg-sky-500 px-2 py-0.5 text-xs font-bold text-white">{{ $reimbNav }}</span>
                            @endif
                        @endif
                        @if($item['route'] === 'approvals.index')
                            @php $pendingNav = app(\App\Services\Approval\ApprovalWorkflowService::class)->countPendingForUser(auth()->user()); @endphp
                            @if($pendingNav > 0)
                                <span class="ml-auto rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $pendingNav }}</span>
                            @endif
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-800 p-4">
                <div class="flex items-center gap-3">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="h-9 w-9 rounded-full ring-2 ring-emerald-500/30">
                    @else
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->company->name }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-1.5 text-left text-xs text-slate-500 transition hover:bg-slate-800 hover:text-slate-300">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex flex-1 flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-lg lg:px-8">
                <h1 class="text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                <div class="flex items-center gap-3">
                    <livewire:notifications.bell />
                    @can('expense.create.own')
                        <a href="{{ route('expenses.create') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-500/25 transition hover:shadow-lg hover:shadow-emerald-500/30">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span class="hidden sm:inline">New expense</span>
                        </a>
                    @endcan
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-600 sm:inline">
                        {{ auth()->user()->getRoleNames()->first() }}
                    </span>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                <x-ui.flash />
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
