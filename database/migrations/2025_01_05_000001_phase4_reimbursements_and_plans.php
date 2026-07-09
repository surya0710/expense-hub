<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('status');
        });

        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('utr')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('payout_batch_id')->nullable()->after('approval_due_at')->constrained()->nullOnDelete();
            $table->timestamp('reimbursed_at')->nullable()->after('payout_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_batch_id');
            $table->dropColumn('reimbursed_at');
        });

        Schema::dropIfExists('payout_batches');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
