<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('domain')->nullable()->unique()->after('slug');
            $table->boolean('domain_auto_join')->default(false)->after('domain');
        });

        $this->backfillDomains();
    }

    protected function backfillDomains(): void
    {
        $companies = \App\Models\Company::query()->with(['users'])->get();

        foreach ($companies as $company) {
            if ($company->domain) {
                continue;
            }

            $owner = $company->users->first(fn ($u) => $u->hasRole('owner'));

            if (! $owner || ! \App\Support\Organization\EmailDomain::isEligibleForAutoJoin($owner->email)) {
                continue;
            }

            $domain = \App\Support\Organization\EmailDomain::fromEmail($owner->email);

            if (\App\Models\Company::query()->where('domain', $domain)->exists()) {
                continue;
            }

            $company->update([
                'domain' => $domain,
                'domain_auto_join' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['domain', 'domain_auto_join']);
        });
    }
};
