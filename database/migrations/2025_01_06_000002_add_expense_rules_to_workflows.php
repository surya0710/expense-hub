<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->decimal('auto_approve_limit', 12, 2)->nullable()->after('petty_cash_limit');
            $table->decimal('receipt_required_above', 12, 2)->nullable()->after('auto_approve_limit');
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropColumn(['auto_approve_limit', 'receipt_required_above']);
        });
    }
};
