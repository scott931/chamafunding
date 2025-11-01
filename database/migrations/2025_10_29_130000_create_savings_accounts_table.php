<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_number')->unique();
            $table->string('account_type')->default('regular'); // regular, fixed_deposit, goal_savings
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            $table->date('maturity_date')->nullable();
            $table->decimal('minimum_balance', 15, 2)->default(0);
            $table->decimal('maximum_balance', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('account_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
