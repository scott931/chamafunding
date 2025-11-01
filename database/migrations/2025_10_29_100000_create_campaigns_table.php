<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->index(); // emergency, project, community, etc.
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('goal_amount')->default(0); // store in minor units (cents)
            $table->unsignedBigInteger('raised_amount')->default(0); // minor units
            $table->string('currency', 3)->default('USD');
            $table->date('deadline')->nullable();
            $table->enum('status', ['draft', 'active', 'successful', 'failed', 'closed'])->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};


