<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('ui_notifications', 'rdv_id')) {
                $table->foreignId('rdv_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('rdvs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('ui_notifications', 'rdv_id')) {
                $table->dropConstrainedForeignId('rdv_id');
            }
        });
    }
};
