<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallCheck extends Command
{
    protected $signature = 'app:install-check';

    protected $description = 'Verify the app is ready for production (shared hosting or VPS)';

    public function handle(): int
    {
        $this->info('ExpenseHub install check');
        $this->newLine();

        $passed = 0;
        $failed = 0;
        $warned = 0;

        foreach ($this->checks() as [$label, $status, $detail]) {
            match ($status) {
                'pass' => $this->line("  <fg=green>✓</> {$label}".($detail ? " — {$detail}" : '')),
                'warn' => $this->line("  <fg=yellow>!</> {$label}".($detail ? " — {$detail}" : '')),
                default => $this->line("  <fg=red>✗</> {$label}".($detail ? " — {$detail}" : '')),
            };

            match ($status) {
                'pass' => $passed++,
                'warn' => $warned++,
                default => $failed++,
            };
        }

        $this->newLine();
        $this->line("Passed: {$passed}  Warnings: {$warned}  Failed: {$failed}");

        if ($failed > 0) {
            $this->error('Fix failed checks before going live.');

            return self::FAILURE;
        }

        if ($warned > 0) {
            $this->warn('Ready with warnings — review items above.');
        } else {
            $this->info('All checks passed.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: string, 2: ?string}>
     */
    protected function checks(): array
    {
        $checks = [];

        $checks[] = ['APP_KEY is set', config('app.key') ? 'pass' : 'fail', null];

        $checks[] = [
            'APP_DEBUG is off in production',
            config('app.debug') && config('app.env') === 'production' ? 'warn' : 'pass',
            config('app.debug') && config('app.env') === 'production' ? 'Set APP_DEBUG=false' : null,
        ];

        $checks[] = [
            'APP_URL is set',
            config('app.url') && config('app.url') !== 'http://localhost' ? 'pass' : 'warn',
            'Must match your live domain for OAuth and emails',
        ];

        try {
            DB::connection()->getPdo();
            $checks[] = ['Database connection', 'pass', null];
        } catch (\Throwable $e) {
            $checks[] = ['Database connection', 'fail', $e->getMessage()];
        }

        $writable = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($writable as $path) {
            $checks[] = [
                'Writable: '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                is_writable($path) ? 'pass' : 'fail',
                is_writable($path) ? null : 'chmod 775 or ask host to fix permissions',
            ];
        }

        $publicStorage = public_path('storage');
        $checks[] = [
            'Storage link (public/storage)',
            File::exists($publicStorage) ? 'pass' : 'warn',
            File::exists($publicStorage) ? null : 'Run: php artisan storage:link',
        ];

        $checks[] = [
            'Queue driver',
            config('queue.default') === 'sync' ? 'pass' : 'warn',
            config('queue.default') === 'sync' ? 'sync is ideal for shared hosting' : 'Use sync unless you have a queue worker cron',
        ];

        $checks[] = [
            'Mail configured',
            config('mail.default') !== 'log' || config('app.env') !== 'production' ? 'pass' : 'warn',
            config('mail.default') === 'log' ? 'Set SMTP in .env for live email' : null,
        ];

        $checks[] = [
            'Google OAuth',
            config('services.google.client_id') && config('services.google.client_secret') ? 'pass' : 'warn',
            'Optional — needed for Sign in with Google',
        ];

        $checks[] = [
            'Scheduler cron',
            'warn',
            'Add cPanel cron: * * * * * php artisan schedule:run (see .env.example)',
        ];

        return $checks;
    }
}
