<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('gateway_account_id')->nullable()->after('plan_id')->constrained('payment_gateway_accounts')->nullOnDelete();
            $table->string('mode', 16)->default('sandbox')->after('gateway'); // sandbox | live
            $table->string('checkout_url', 1000)->nullable()->after('gateway_reference');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->unique()->after('number');
            $table->string('payment_status', 16)->default('unpaid')->index()->after('status'); // unpaid | paid
            $table->timestamp('paid_at')->nullable()->after('invoiced_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gateway_account_id');
            $table->dropColumn(['mode', 'checkout_url']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_status', 'paid_at']);
        });
    }
};
