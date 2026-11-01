<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // bot owner
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->default('bot'); // bot | manual
            $table->text('summary')->nullable();      // AI-generated summary of the chat
            $table->text('needs')->nullable();        // what the client needs
            $table->boolean('order_confirmed')->default(false)->index();
            $table->decimal('order_total', 10, 2)->nullable();
            $table->string('status')->default('new')->index(); // new | contacted | won | lost
            $table->boolean('is_read')->default(false)->index(); // notification counter
            $table->json('meta')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
