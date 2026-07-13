<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('compare_at_price', 15, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // Custom ecommerce features matching the old database schema
            $table->string('material')->nullable();
            $table->string('print_detail')->nullable();
            $table->string('style')->nullable();
            $table->text('care_instructions')->nullable();
            $table->enum('badge', ['NEW', 'BESTSELLER', 'SALE'])->nullable();
            $table->integer('fake_sold_count')->default(0);
            $table->integer('min_fake_views')->default(5);
            $table->integer('max_fake_views')->default(20);
            $table->boolean('is_web_exclusive')->default(false);
            $table->boolean('is_limited')->default(false);
            $table->integer('limited_max_stock')->nullable();
            $table->string('model_height')->nullable();
            $table->string('model_weight')->nullable();
            $table->string('model_size_worn')->nullable();
            $table->string('size_chart_url')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
