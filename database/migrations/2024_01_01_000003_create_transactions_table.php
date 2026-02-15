<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');

            $table->string('type', 50);

            $table->bigInteger('amount_kobo');

            $table->string('status', 20)->default('completed');


            $table->json('metadata')->nullable();

            $table->string('reference', 100)->unique();

            $table->unsignedBigInteger('balance_after')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('type');
            $table->index('status');
            $table->index('reference');
            $table->index('created_at');
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
