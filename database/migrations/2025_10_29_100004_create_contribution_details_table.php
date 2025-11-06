<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contribution_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('campaign_contributions')->cascadeOnDelete();
            $table->foreignId('reward_tier_id')->nullable()->constrained('reward_tiers')->nullOnDelete();

            // Shipping information
            $table->string('shipping_name')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_phone')->nullable();

            // Survey/fulfillment information
            $table->json('survey_responses')->nullable(); // store survey answers as JSON
            $table->boolean('survey_completed')->default(false);
            $table->timestamp('survey_completed_at')->nullable();

            // Delivery tracking
            $table->string('tracking_number')->nullable();
            $table->string('tracking_carrier')->nullable();
            $table->enum('delivery_status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Digital rewards
            $table->json('digital_rewards')->nullable(); // store download links, codes, etc.

            // Additional metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contribution_id']);
            $table->index(['delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_details');
    }
};

