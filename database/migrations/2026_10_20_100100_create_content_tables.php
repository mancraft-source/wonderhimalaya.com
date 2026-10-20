<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('path')->unique();
            $table->string('template')->default('default');
            $table->text('excerpt')->nullable();
            $table->json('blocks')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('bio')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->json('social')->nullable();
            $table->boolean('is_guide')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('seo')->nullable();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->string('wp_slug')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('author_country')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('source')->default('manual');
            $table->string('source_url')->nullable();
            $table->date('reviewed_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->unique();
            $table->json('items')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['menus', 'settings', 'redirects', 'testimonials', 'posts', 'team_members', 'post_categories', 'pages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
