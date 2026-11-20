<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_email')->nullable();
            $table->string('email_verification_code_hash')->nullable();
            $table->timestamp('email_change_expires_at')->nullable();
            $table->json('notification_preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pending_email',
                'email_verification_code_hash',
                'email_change_expires_at',
                'notification_preferences',
            ]);
        });
    }
};
