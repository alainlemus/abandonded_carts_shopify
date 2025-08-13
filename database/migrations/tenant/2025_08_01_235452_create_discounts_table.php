<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abandoned_cart_id');
            $table->foreign('abandoned_cart_id')->references('id')->on('abandoned_carts')->onDelete('cascade');
            $table->unsignedBigInteger('shop_id'); // Nueva columna
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade'); // Clave foránea
            $table->string('code')->unique();
            $table->decimal('amount', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
