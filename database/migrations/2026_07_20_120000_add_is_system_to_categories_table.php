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
        if (!Schema::hasColumn('categories', 'is_system')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });

            // Mark "chua-phan-loai" category as is_system = true
            DB::table('categories')
                ->where('slug', 'chua-phan-loai')
                ->update(['is_system' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'is_system')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
