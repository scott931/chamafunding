<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // card, bank_account, mobile_money, digital_wallet
            $table->string('provider'); // stripe, paypal, mpesa, flutterwave, etc.
            $table->string('external_id')->nullable(); // ID from payment provider
            $table->string('last_four')->nullable();
            $table->string('brand')->nullable(); // visa, mastercard, etc.
            $table->string('exp_month')->nullable();
            $table->string('exp_year')->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
