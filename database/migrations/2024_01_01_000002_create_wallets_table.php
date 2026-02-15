<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->unsignedBigInteger('balance_kobo')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->index('balance_kobo');


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
