<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('previous_login_at')->nullable()->after('last_login_ip');
            $table->string('previous_login_ip', 45)->nullable()->after('previous_login_at');
            $table->json('notification_preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_ip', 'previous_login_at', 'previous_login_ip', 'notification_preferences']);
        });
    }
};
