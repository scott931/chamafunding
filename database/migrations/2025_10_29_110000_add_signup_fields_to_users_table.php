<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('membership_type')->nullable()->after('phone');
            $table->unsignedBigInteger('preferred_contribution_amount')->nullable()->after('membership_type'); // minor units
            $table->enum('payment_frequency', ['monthly', 'weekly', 'quarterly'])->nullable()->after('preferred_contribution_amount');
            $table->string('referral_code')->nullable()->after('payment_frequency');
            $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            $table->string('otp_code', 10)->nullable()->after('privacy_accepted_at');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'membership_type',
                'preferred_contribution_amount',
                'payment_frequency',
                'referral_code',
                'terms_accepted_at',
                'privacy_accepted_at',
                'otp_code',
                'otp_expires_at',
            ]);
        });
    }
};


