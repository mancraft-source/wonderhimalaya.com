<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('agency');
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('prospect');
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('country')->nullable();
            $table->string('source')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type')->index();
            $table->string('status')->default('new')->index();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_departure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('destinations')->nullable();
            $table->json('interests')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('travel_month')->nullable();
            $table->string('date_flexibility')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('budget')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->text('message')->nullable();
            $table->string('source')->nullable();
            $table->json('utm')->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('landing_page', 1000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('taskable');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('todo');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_departure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('draft')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('pax')->default(1);
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedTinyInteger('deposit_percent')->default(20);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->longText('introduction')->nullable();
            $table->longText('terms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_departure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending_deposit')->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('pax')->default(1);
            $table->char('currency', 3)->default('USD');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->date('balance_due_on')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('emergency_contact')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('travellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->text('date_of_birth')->nullable();
            $table->text('passport_number')->nullable();
            $table->text('passport_expiry')->nullable();
            $table->text('insurance_provider')->nullable();
            $table->text('insurance_policy')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('dietary')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('payable');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway')->nullable();
            $table->string('type')->default('deposit');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status')->default('pending')->index();
            $table->string('gateway_reference')->nullable()->index();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'travellers', 'bookings', 'quote_items', 'quotes', 'crm_tasks', 'notes', 'leads', 'contacts', 'companies'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
