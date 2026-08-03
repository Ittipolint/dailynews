<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('line_oa_basic_id')->nullable()->after('line_oa_user_id');
            $table->string('line_oa_channel_id')->nullable()->after('line_oa_basic_id');
            $table->text('line_oa_channel_secret')->nullable()->after('line_oa_channel_id');
            $table->string('line_oa_webhook_url')->nullable()->after('line_oa_channel_secret');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn([
                'line_oa_basic_id',
                'line_oa_channel_id',
                'line_oa_channel_secret',
                'line_oa_webhook_url',
            ]);
        });
    }
};
