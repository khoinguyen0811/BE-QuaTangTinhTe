<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 80)->unique();
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('draft_content')->nullable();
            $table->json('published_content')->nullable();
            $table->unsignedInteger('draft_revision')->default(0);
            $table->unsignedInteger('published_revision')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('page_layout_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_layout_id')->constrained('page_layouts')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event', 30)->default('draft');
            $table->json('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['page_layout_id', 'revision']);
            $table->index(['page_layout_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_layout_revisions');
        Schema::dropIfExists('page_layouts');
    }
};
