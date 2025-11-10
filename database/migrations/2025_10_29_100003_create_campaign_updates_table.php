<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['update', 'milestone', 'announcement', 'shipping', 'delay'])->default('update');
            $table->boolean('is_public')->default(true); // visible to all backers
            $table->boolean('send_notification')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'published_at']);
            $table->index(['campaign_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_updates');
    }
};

