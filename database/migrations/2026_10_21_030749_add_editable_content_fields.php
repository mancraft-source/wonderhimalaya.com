<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->json('packing')->nullable()->after('videos');
            $table->text('itinerary_note')->nullable()->after('packing');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->json('content')->nullable()->after('blocks');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('author_email')->nullable()->after('author_country');
            $table->date('travelled_on')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('trips', fn (Blueprint $table) => $table->dropColumn(['packing', 'itinerary_note']));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('content'));
        Schema::table('testimonials', fn (Blueprint $table) => $table->dropColumn(['author_email', 'travelled_on']));
    }
};
