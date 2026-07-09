<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-marketing.seo :canonical="route('home')" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 font-sans text-white antialiased">
    @php
        $plans = config('subscription.plans');
        $planKeys = array_keys($plans);
        $featuredPlan = config('marketing.featured_plan', 'starter');
        $planHighlights = config('marketing.plan_highlights');
        $comparisonRows = config('marketing.comparison_rows');
        $faqs = config('marketing.faqs');
    @endphp

    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-32 top-20 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute -right-32 top-1/3 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-indigo-500/10 blur-3xl"></div>
    </div>

    {{-- Header --}}
    <header class="relative sticky top-0 z-40 border-b border-white/5 bg-slate-950/80 backdrop-blur-lg">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xl font-bold">ExpenseHub</span>
            </a>
            <nav class="hidden items-center gap-6 md:flex">
                <a href="#features" class="text-sm font-medium text-slate-400 transition hover:text-white">Features</a>
                <a href="#pricing" class="text-sm font-medium text-slate-400 transition hover:text-white">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-slate-400 transition hover:text-white">FAQ</a>
            </nav>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-300 hover:text-white">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2 text-sm font-medium text-slate-300 hover:text-white sm:inline">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-2 text-sm font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40">
                        Start free trial
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="relative">
        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-4 py-20 text-center lg:py-28">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-sm text-emerald-400">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                14-day free trial · No credit card
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl lg:text-7xl">
                Every rupee.<br>
                <span class="bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">One dashboard.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-400">
                From ₹50 tea bills to ₹5 lakh machinery — track, approve, reimburse, and report every expense. Replace WhatsApp photos and Excel chaos with ExpenseHub.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-8 py-3.5 text-sm font-semibold shadow-xl shadow-emerald-500/30 transition hover:shadow-emerald-500/50">
                    Get started free
                </a>
                <a href="#pricing" class="rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-sm font-semibold backdrop-blur transition hover:bg-white/10">
                    View pricing
                </a>
            </div>
            <p class="mt-6 text-xs text-slate-500">Trusted by growing teams across retail, manufacturing, agencies &amp; services</p>
        </section>

        {{-- Features --}}
        <section id="features" class="scroll-mt-20 border-t border-white/5 py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Built for how Indian teams actually spend</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-slate-400">Petty cash at the branch, UPI reimbursements at HQ, GST-ready reports for your CA — all in one place.</p>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        ['title' => 'Smart approvals', 'desc' => 'Auto-approve small spends. Route ₹5K+ through managers, admins, and owners with configurable workflows.', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['title' => 'Receipt capture', 'desc' => 'Upload bills from any device. Stored securely on local disk or S3 — your data stays isolated per company.', 'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z'],
                        ['title' => 'Petty cash wallets', 'desc' => 'Track site-level cash with custodians, top-ups, reconciliation, and low-balance alerts.', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['title' => 'Budgets & alerts', 'desc' => 'Set category and employee limits. Get warned at 80% and optionally block overspend.', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['title' => 'Reimbursement batches', 'desc' => 'Group approved expenses, pay via bank/UPI, record UTR, and notify employees automatically.', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['title' => 'Reports & GST', 'desc' => 'Expense register, category summaries, employee spend, and GST breakup — export CSV or PDF anytime.', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ] as $feature)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm transition hover:border-emerald-500/30 hover:bg-white/[0.07]">
                            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20">
                                <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                            </div>
                            <h3 class="font-semibold text-white">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Pricing --}}
        <section id="pricing" class="scroll-mt-20 border-t border-white/5 py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Simple, transparent pricing</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-slate-400">Start free. Upgrade when your team grows. All prices in INR, billed monthly.</p>
                </div>

                {{-- Plan cards --}}
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($planKeys as $key)
                        @php
                            $plan = $plans[$key];
                            $isFeatured = $key === $featuredPlan;
                            $highlights = $planHighlights[$key] ?? [];
                        @endphp
                        <div @class([
                            'relative flex flex-col rounded-2xl border p-6 backdrop-blur-sm transition',
                            'border-emerald-400/50 bg-gradient-to-b from-emerald-500/10 to-transparent shadow-lg shadow-emerald-500/10 ring-1 ring-emerald-400/30' => $isFeatured,
                            'border-white/10 bg-white/5 hover:border-white/20' => ! $isFeatured,
                        ])>
                            @if($isFeatured)
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-emerald-500 px-3 py-0.5 text-xs font-bold uppercase tracking-wide text-white">Most popular</span>
                            @endif
                            <p class="text-lg font-bold text-white">{{ $plan['name'] }}</p>
                            <div class="mt-3 flex items-baseline gap-1">
                                @if(($plan['price'] ?? 0) > 0)
                                    <span class="text-4xl font-extrabold text-white">₹{{ number_format($plan['price']) }}</span>
                                    <span class="text-sm text-slate-400">/month</span>
                                @else
                                    <span class="text-4xl font-extrabold text-white">Free</span>
                                @endif
                            </div>
                            <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-300">
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ $plan['users'] }} team {{ Str::plural('member', $plan['users']) }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    @if($plan['expenses_per_month'])
                                        {{ number_format($plan['expenses_per_month']) }} expenses/month
                                    @else
                                        Unlimited expenses
                                    @endif
                                </li>
                                @foreach($highlights as $highlight)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        {{ $highlight }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" @class([
                                'mt-8 block rounded-xl py-2.5 text-center text-sm font-semibold transition',
                                'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md hover:shadow-lg' => $isFeatured,
                                'border border-white/20 bg-white/5 text-white hover:bg-white/10' => ! $isFeatured,
                            ])>
                                {{ $key === 'free' ? 'Start free' : 'Start trial' }}
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Enterprise callout --}}
                <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 px-6 py-5 text-center sm:flex sm:items-center sm:justify-between sm:text-left">
                    <div>
                        <p class="font-semibold text-white">Need Enterprise?</p>
                        <p class="mt-1 text-sm text-slate-400">100+ users, SSO, on-prem deployment, or custom integrations — talk to us.</p>
                    </div>
                    <a href="mailto:sales@expensehub.in" class="mt-4 inline-block rounded-xl border border-white/20 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:mt-0">
                        Contact sales
                    </a>
                </div>

                {{-- Comparison table --}}
                <div class="mt-12 overflow-hidden rounded-2xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="border-b border-white/10 bg-white/5">
                                    <th class="px-4 py-3 text-left font-semibold text-slate-300">Compare plans</th>
                                    @foreach($planKeys as $key)
                                        <th class="px-4 py-3 text-center font-semibold text-white">{{ $plans[$key]['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($comparisonRows as $row)
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-3 text-slate-400">{{ $row['label'] }}</td>
                                        @foreach($planKeys as $key)
                                            @php
                                                if (($row['dynamic'] ?? null) === 'users') {
                                                    $cell = $plans[$key]['users'];
                                                } elseif (($row['dynamic'] ?? null) === 'expenses') {
                                                    $cell = $plans[$key]['expenses_per_month']
                                                        ? number_format($plans[$key]['expenses_per_month'])
                                                        : 'Unlimited';
                                                } else {
                                                    $cell = $row['values'][$key] ?? false;
                                                }
                                            @endphp
                                            <td class="px-4 py-3 text-center">
                                                @if(is_bool($cell))
                                                    @if($cell)
                                                        <svg class="mx-auto h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    @else
                                                        <span class="text-slate-600">—</span>
                                                    @endif
                                                @else
                                                    <span class="font-medium text-slate-200">{{ $cell }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-20 border-t border-white/5 py-20">
            <div class="mx-auto max-w-3xl px-4">
                <div class="mb-10 text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Frequently asked questions</h2>
                </div>
                <div class="space-y-4">
                    @foreach($faqs as $faq)
                        <details class="group rounded-2xl border border-white/10 bg-white/5 open:bg-white/[0.07]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold text-white marker:content-none [&::-webkit-details-marker]:hidden">
                                <span>{{ $faq['q'] }}</span>
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="border-t border-white/5 px-5 py-4 text-sm leading-relaxed text-slate-400">
                                {{ $faq['a'] }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="border-t border-white/5 py-20">
            <div class="mx-auto max-w-3xl px-4 text-center">
                <h2 class="text-3xl font-bold tracking-tight">Ready to see where every rupee goes?</h2>
                <p class="mt-3 text-slate-400">Set up your company in minutes. Invite your team and submit your first expense today.</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-8 py-3.5 text-sm font-semibold shadow-xl shadow-emerald-500/30 transition hover:shadow-emerald-500/50">
                        Start 14-day free trial
                    </a>
                    <a href="{{ route('auth.google') }}" class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-sm font-semibold backdrop-blur transition hover:bg-white/10">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Continue with Google
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="relative border-t border-white/5 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 sm:flex-row">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="font-semibold text-slate-300">ExpenseHub</span>
            </div>
            <p class="text-sm text-slate-600">&copy; {{ date('Y') }} ExpenseHub. Expense management for Indian businesses.</p>
            <div class="flex gap-4 text-sm text-slate-500">
                <a href="#pricing" class="hover:text-slate-300">Pricing</a>
                <a href="{{ route('login') }}" class="hover:text-slate-300">Sign in</a>
                <a href="{{ route('register') }}" class="hover:text-slate-300">Register</a>
            </div>
        </div>
    </footer>
</body>
</html>
