<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('phone');
        });

        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['expense_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_approvals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url']);
        });
    }
};
