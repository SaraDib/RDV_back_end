<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('ui_notifications', 'status')) {
                $table->string('status')->default('unread'); // ✅ بلا after
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('ui_notifications', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
