<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports_cache', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // campaign_analytics, financial_summary, user_activity, etc.
            $table->string('cache_key')->unique();
            $table->json('data');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['report_type', 'expires_at']);
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports_cache');
    }
};
