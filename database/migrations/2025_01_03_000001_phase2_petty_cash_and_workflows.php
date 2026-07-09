<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('site')->nullable();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->decimal('low_balance_threshold_percent', 5, 2)->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('petty_cash_wallets')->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });

        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('escalation_hours')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_default']);
        });

        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->string('approver_type');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_role')->nullable();
            $table->unsignedSmallInteger('sla_hours')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'level']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('wallet_id')->nullable()->after('payment_mode')->constrained('petty_cash_wallets')->nullOnDelete();
            $table->unsignedTinyInteger('current_approval_step')->nullable()->after('status');
            $table->timestamp('approval_due_at')->nullable()->after('current_approval_step');
        });

        Schema::table('expense_approvals', function (Blueprint $table) {
            $table->unsignedTinyInteger('step')->nullable()->after('expense_id');
            $table->timestamp('decided_at')->nullable()->after('comment');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('expense_approvals', function (Blueprint $table) {
            $table->dropColumn(['step', 'decided_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wallet_id');
            $table->dropColumn(['current_approval_step', 'approval_due_at']);
        });

        Schema::dropIfExists('approval_workflow_steps');
        Schema::dropIfExists('approval_workflows');
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('petty_cash_wallets');
    }
};
