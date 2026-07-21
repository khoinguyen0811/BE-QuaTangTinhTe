<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePageTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('pagebuilder.storage.database.prefix') . 'page_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id');
            $table->string('locale', 50);
            $table->string('title', 255);
            $table->string('meta_title', 255);
            $table->string('meta_description', 255);
            $table->string('route', 255);
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
            $table->foreign('page_id')->references('id')
                ->on(config('pagebuilder.storage.database.prefix') . 'pages')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('pagebuilder.storage.database.prefix') . 'page_translations');
    }
}
