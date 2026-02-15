<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('crypto_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('crypto_type', 10);

            $table->string('trade_type', 10);


            $table->decimal('crypto_amount', 20, 8);

            $table->unsignedBigInteger('naira_amount_kobo');


            $table->decimal('rate_ngn', 20, 2);

            $table->unsignedBigInteger('fee_kobo');

            $table->decimal('fee_percentage', 5, 2);

            $table->string('status', 20)->default('completed');

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('transaction_id');
            $table->index('user_id');
            $table->index('crypto_type');
            $table->index('trade_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'crypto_type']);
            $table->index(['user_id', 'trade_type']);
            $table->index(['crypto_type', 'created_at']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('crypto_trades');
    }
};
