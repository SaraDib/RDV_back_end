<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // basic / pro / enterprise
            $table->string('name'); // Basic / Pro ...
            $table->decimal('price_monthly', 10, 2)->default(0);

            // limits (null = unlimited)
            $table->unsignedInteger('max_agents')->nullable();
            $table->unsignedInteger('max_services')->nullable();
            $table->unsignedInteger('max_rdvs_per_month')->nullable();

            $table->boolean('whatsapp_enabled')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
