<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->unsignedInteger('orig_price');
            $table->string('emoji')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->json('variants')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
