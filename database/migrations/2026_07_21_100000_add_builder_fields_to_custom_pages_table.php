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
        Schema::table('custom_pages', function (Blueprint $table) {
            $table->unsignedInteger('builder_page_id')->nullable()->index()->after('id');
            $table->string('builder_driver')->default('legacy')->after('builder_page_id');

            // Apply foreign key dynamically
            $table->foreign('builder_page_id')
                ->references('id')
                ->on('pagebuilder_pages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_pages', function (Blueprint $table) {
            $table->dropForeign(['builder_page_id']);
            $table->dropColumn(['builder_page_id', 'builder_driver']);
        });
    }
};
