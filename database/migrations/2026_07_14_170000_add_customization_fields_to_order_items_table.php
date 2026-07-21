<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'custom_text')) {
                $table->text('custom_text')->nullable()->after('total');
            }

            if (! Schema::hasColumn('order_items', 'custom_image_name')) {
                $table->string('custom_image_name')->nullable()->after('custom_text');
            }

            if (! Schema::hasColumn('order_items', 'custom_image_url')) {
                $table->text('custom_image_url')->nullable()->after('custom_image_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('order_items', 'custom_image_url') ? 'custom_image_url' : null,
                Schema::hasColumn('order_items', 'custom_image_name') ? 'custom_image_name' : null,
                Schema::hasColumn('order_items', 'custom_text') ? 'custom_text' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
