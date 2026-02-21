<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {

            if (!Schema::hasColumn('ui_notifications', 'type')) {
                $table->string('type')->nullable()->after('rdv_id');
            }

            if (!Schema::hasColumn('ui_notifications', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            // ✅ هذا هو اللي كان كيدير duplicate
            if (!Schema::hasColumn('ui_notifications', 'body')) {
                $table->text('body')->nullable()->after('title');
            }

            if (!Schema::hasColumn('ui_notifications', 'status')) {
                $table->string('status')->default('unread')->after('body');
            }

            if (!Schema::hasColumn('ui_notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {

            if (Schema::hasColumn('ui_notifications', 'action_url')) {
                $table->dropColumn('action_url');
            }

            if (Schema::hasColumn('ui_notifications', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('ui_notifications', 'body')) {
                $table->dropColumn('body');
            }

            if (Schema::hasColumn('ui_notifications', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('ui_notifications', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
