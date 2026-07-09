<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-marketing.seo
        :title="isset($title) ? $title.' — '.config('app.name', 'ExpenseHub') : null"
        :description="$seoDescription ?? 'Sign in or register for ExpenseHub — expense management for Indian businesses.'"
    />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 font-sans antialiased">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -left-40 top-0 h-[500px] w-[500px] rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute -right-40 bottom-0 h-[400px] w-[400px] rounded-full bg-teal-500/10 blur-3xl"></div>
    </div>

    <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 shadow-lg shadow-emerald-500/30">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-2xl font-bold text-white">ExpenseHub</span>
        </a>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
