<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->timestamps();

                // Unique constraint to prevent duplicate links
                $table->unique(['product_id', 'category_id']);
            });

            // Migrate existing data from products.category_id to category_product
            if (Schema::hasColumn('products', 'category_id')) {
                $existingProducts = DB::table('products')
                    ->whereNotNull('category_id')
                    ->select('id', 'category_id', 'created_at', 'updated_at')
                    ->get();

                foreach ($existingProducts as $prod) {
                    // Check if category still exists in categories table to avoid foreign key violations
                    $catExists = DB::table('categories')->where('id', $prod->category_id)->exists();
                    if ($catExists) {
                        DB::table('category_product')->insertOrIgnore([
                            'product_id' => $prod->id,
                            'category_id' => $prod->category_id,
                            'created_at' => $prod->created_at ?? now(),
                            'updated_at' => $prod->updated_at ?? now(),
                        ]);
                    }
                }

                // Make products.category_id nullable for backward compatibility
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedBigInteger('category_id')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
