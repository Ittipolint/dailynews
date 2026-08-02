<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('news_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url')->nullable();
            $table->string('locale', 5)->default('en'); // source language
            $table->enum('fetch_type', ['rss', 'api', 'crawl'])->default('rss');
            $table->string('feed_url')->nullable(); // RSS feed or API endpoint
            $table->string('cron_expression')->default('0 * * * *'); // hourly by default
            $table->jsonb('credentials')->nullable(); // encrypted credentials JSON
            $table->jsonb('config')->nullable(); // fetch params, selectors, headers etc.
            $table->string('category')->nullable(); // default category
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->string('last_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('pgsql')->create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('news', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('news_sources')->cascadeOnDelete();
            $table->string('source_url')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('body')->nullable();
            $table->string('category')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('lang', 5)->nullable(); // source language
            $table->string('content_hash', 64)->index(); // dedup hash of normalized headline+url
            $table->string('status', 20)->default('new'); // new|translated|indexed|failed
            $table->string('sentiment', 10)->nullable(); // optional
            $table->boolean('is_breaking')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'source_id']);
            $table->index('category');
            $table->index('is_breaking');
        });

        Schema::connection('pgsql')->create('news_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->string('locale', 5); // th|en|zh
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending'); // pending|translated|failed
            $table->text('error_message')->nullable();
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();

            $table->unique(['news_id', 'locale']);
        });

        Schema::connection('pgsql')->create('member_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_type_id')->constrained('member_types');
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('line_user_id')->nullable(); // LINE personal
            $table->string('line_oa_user_id')->nullable(); // LINE OA
            $table->string('preferred_locale', 5)->default('th'); // news language preference
            $table->string('status', 20)->default('active'); // active|expired|trial|suspended
            $table->boolean('is_active')->default(true);
            // Phase 2 billing readiness
            $table->timestamp('plan_start_date')->nullable();
            $table->timestamp('plan_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
        });

        Schema::connection('pgsql')->create('member_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('channel_type', ['line_personal', 'line_oa', 'email']);
            $table->jsonb('credentials')->nullable(); // encrypted: email addr, LINE recipient, SMTP etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['member_id', 'channel_type']);
        });

        Schema::connection('pgsql')->create('member_interests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('type'); // category|tag|keyword
            $table->string('value');
            $table->jsonb('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['member_id', 'type', 'value']);
        });

        Schema::connection('pgsql')->create('member_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('name');
            $table->string('cron_expression'); // e.g. 0 8 * * *
            $table->jsonb('channels')->nullable(); // ["email","line_personal"]
            $table->jsonb('categories')->nullable(); // ["technology","business"]
            $table->jsonb('languages')->nullable(); // ["th","en","zh"]
            $table->integer('limit')->default(10); // max news per send
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('member_schedules')->nullOnDelete();
            $table->enum('channel_type', ['line_personal', 'line_oa', 'email']);
            $table->jsonb('news_ids')->nullable();
            $table->string('status', 20); // success|failed|partial
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'channel_type']);
            $table->index('status');
        });

        // Phase 2 readiness
        Schema::connection('pgsql')->create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->jsonb('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->timestamp('plan_start_date')->nullable();
            $table->timestamp('plan_end_date')->nullable();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('status', 20)->default('active'); // active|expired|trial|cancelled
            $table->string('payment_gateway', 50)->nullable();
            $table->string('payment_ref')->nullable();
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('credentials', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique(); // e.g. line_channel, smtp, gemini_api, n8n_api
            $table->string('name');
            $table->jsonb('config')->nullable(); // encrypted values
            $table->boolean('is_active')->default(true);
            $table->string('updated_by')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('action');
            $table->string('entity');
            $table->string('entity_id')->nullable();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->timestamps();

            $table->index(['entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        $tables = [
            'audit_logs', 'credentials', 'subscriptions', 'packages', 'delivery_logs',
            'member_schedules', 'member_interests', 'member_channels', 'members',
            'member_types', 'news_translations', 'news', 'categories', 'news_sources',
        ];

        foreach ($tables as $table) {
            Schema::connection('pgsql')->dropIfExists($table);
        }
    }
};
