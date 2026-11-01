<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_label')->nullable()->after('subscription_plan');
            $table->decimal('plan_amount', 10, 2)->nullable()->after('plan_label');
            $table->string('plan_currency', 8)->default('USD')->after('plan_amount');
            $table->unsignedInteger('plan_days')->nullable()->after('plan_currency');
            $table->foreignId('payment_gateway_account_id')->nullable()->after('plan_days')
                ->constrained('payment_gateway_accounts')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('label')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_gateway_account_id');
            $table->dropColumn(['plan_label', 'plan_amount', 'plan_currency', 'plan_days']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
