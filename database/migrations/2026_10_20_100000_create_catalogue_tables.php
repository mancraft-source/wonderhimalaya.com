<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('destinations')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('intro')->nullable();
            $table->longText('description')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->longText('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('difficulties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('level')->default(1);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_code')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('overview')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('duration_nights')->nullable();
            $table->unsignedSmallInteger('max_altitude_m')->nullable();
            $table->unsignedSmallInteger('group_min')->nullable();
            $table->unsignedSmallInteger('group_max')->nullable();
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->string('best_season')->nullable();
            $table->string('accommodation')->nullable();
            $table->string('meals')->nullable();
            $table->string('transport')->nullable();
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->foreignId('difficulty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->json('highlights')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->json('facts')->nullable();
            $table->json('videos')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_fixed_departure')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_top_selling')->default(false);
            $table->boolean('is_trip_of_month')->default(false);
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->string('wp_slug')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trip_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title', 500);
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('altitude_m')->nullable();
            $table->decimal('distance_km', 6, 1)->nullable();
            $table->string('duration')->nullable();
            $table->json('meals')->nullable();
            $table->string('accommodation')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('trip_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('question', 500);
            $table->longText('answer');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('trip_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('pax_min')->default(1);
            $table->unsignedSmallInteger('pax_max')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('trip_departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('seats_total')->default(12);
            $table->unsignedSmallInteger('seats_booked')->default(0);
            $table->string('status')->default('available');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('destination_trip', function (Blueprint $table) {
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->primary(['destination_id', 'trip_id']);
        });

        Schema::create('activity_trip', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->primary(['activity_id', 'trip_id']);
        });

        Schema::create('tier_trip', function (Blueprint $table) {
            $table->foreignId('tier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->primary(['tier_id', 'trip_id']);
        });

        Schema::create('related_trips', function (Blueprint $table) {
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_trip_id')->constrained('trips')->cascadeOnDelete();
            $table->primary(['trip_id', 'related_trip_id']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        foreach (['taggables', 'related_trips', 'tier_trip', 'activity_trip', 'destination_trip', 'trip_departures',
            'trip_prices', 'trip_faqs', 'trip_days', 'trips', 'tags', 'tiers', 'difficulties', 'activities', 'destinations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
