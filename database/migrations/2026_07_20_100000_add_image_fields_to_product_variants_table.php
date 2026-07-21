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
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'image_url')) {
                $table->string('image_url', 2048)->nullable()->after('sku');
            }
            if (!Schema::hasColumn('product_variants', 'images')) {
                $table->json('images')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('product_variants', 'compare_at_price')) {
                $table->decimal('compare_at_price', 15, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('product_variants', 'allow_out_of_stock_order')) {
                $table->boolean('allow_out_of_stock_order')->default(false)->after('stock_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('product_variants', 'image_url')) {
                $columns[] = 'image_url';
            }
            if (Schema::hasColumn('product_variants', 'images')) {
                $columns[] = 'images';
            }
            if (Schema::hasColumn('product_variants', 'compare_at_price')) {
                $columns[] = 'compare_at_price';
            }
            if (Schema::hasColumn('product_variants', 'allow_out_of_stock_order')) {
                $columns[] = 'allow_out_of_stock_order';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
