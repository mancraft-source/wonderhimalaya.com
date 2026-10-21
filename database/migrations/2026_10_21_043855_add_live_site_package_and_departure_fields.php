<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_prices', function (Blueprint $table) {
            $table->decimal('child_price', 10, 2)->nullable()->after('sale_price');
            $table->text('description')->nullable()->after('child_price');
            $table->boolean('is_primary')->default(false)->after('description');
        });

        Schema::table('trips', function (Blueprint $table) {
            // "daily" = joins any day (current WP Travel Engine setup); "scheduled" = listed trip_departures only.
            $table->string('departure_mode')->default('scheduled')->after('is_fixed_departure');
            $table->unsignedSmallInteger('departure_cutoff_days')->default(10)->after('departure_mode');
            $table->unsignedSmallInteger('departure_capacity')->nullable()->after('departure_cutoff_days');
        });
    }

    public function down(): void
    {
        Schema::table('trip_prices', fn (Blueprint $table) => $table->dropColumn(['child_price', 'description', 'is_primary']));
        Schema::table('trips', fn (Blueprint $table) => $table->dropColumn(['departure_mode', 'departure_cutoff_days', 'departure_capacity']));
    }
};
