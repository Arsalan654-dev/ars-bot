<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // bot owner
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->text('items')->nullable(); // json list of services/products
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('status')->default('confirmed')->index(); // confirmed | cancelled
            $table->string('invoice_path')->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
