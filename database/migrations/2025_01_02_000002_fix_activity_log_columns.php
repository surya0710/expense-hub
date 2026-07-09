<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'event')) {
                $table->string('event')->nullable()->after('subject_type');
            }
            if (! Schema::hasColumn($tableName, 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            }
        });
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name');

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'event')) {
                $table->dropColumn('event');
            }
            if (Schema::hasColumn($tableName, 'batch_uuid')) {
                $table->dropColumn('batch_uuid');
            }
        });
    }
};
