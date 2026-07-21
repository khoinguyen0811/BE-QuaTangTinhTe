<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_pages', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();

            $table->unsignedInteger('lock_version')->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_pages');
    }
};
