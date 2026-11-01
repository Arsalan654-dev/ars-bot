<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->index(); // super_admin | user
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_paid')->default(false)->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('subscription_plan')->nullable(); // weekly | monthly | custom
            $table->unsignedInteger('subscription_days')->nullable();
            $table->timestamp('subscription_ends_at')->nullable()->index();
            $table->boolean('bot_active')->default(false)->index();
            $table->string('api_key', 64)->nullable()->unique();
            $table->string('avatar_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('bot_name')->default('L.I.S.A');
            $table->string('bot_color', 16)->default('#000000');
            $table->string('bot_theme', 32)->default('lisa');
            $table->text('knowledge_base')->nullable(); // free-form knowledge for AI brain
            $table->text('about_content')->nullable();  // per-user about page content
            $table->boolean('payment_reminder_sent')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'company', 'phone', 'is_paid', 'paid_at',
                'subscription_plan', 'subscription_days', 'subscription_ends_at',
                'bot_active', 'api_key', 'avatar_path', 'logo_path',
                'bot_name', 'bot_color', 'bot_theme', 'knowledge_base',
                'about_content', 'payment_reminder_sent',
            ]);
        });
    }
};
