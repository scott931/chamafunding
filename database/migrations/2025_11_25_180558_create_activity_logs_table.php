<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('activity_type'); // login, logout, register, view_page, etc.
            $table->string('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('device_name')->nullable(); // iPhone, Windows, etc.
            $table->string('browser')->nullable(); // Chrome, Safari, Firefox, etc.
            $table->string('browser_version')->nullable();
            $table->string('os')->nullable(); // iOS, Windows, macOS, Android, Linux
            $table->string('os_version')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // GET, POST, etc.
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('activity_type');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
