<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('reward_tiers')) {
            return;
        }

        Schema::create('reward_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('name'); // e.g., "Early Bird Special", "Digital Download"
            $table->text('description')->nullable();
            $table->unsignedBigInteger('minimum_amount'); // minimum pledge amount in cents
            $table->unsignedBigInteger('maximum_amount')->nullable(); // optional maximum
            $table->integer('estimated_delivery_month')->nullable(); // delivery month
            $table->integer('estimated_delivery_year')->nullable(); // delivery year
            $table->enum('reward_type', ['physical', 'digital', 'experience', 'custom'])->default('custom');
            $table->boolean('requires_shipping')->default(false);
            $table->boolean('is_limited')->default(false);
            $table->integer('quantity_limit')->nullable(); // limited quantity available
            $table->integer('quantity_claimed')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tiers');
    }
};

