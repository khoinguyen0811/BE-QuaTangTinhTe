<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    public function up()
    {
        Schema::create(config('pagebuilder.storage.database.prefix') . 'pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->string('layout', 256);
            $table->longText('data')->nullable(); // Holds draft GrapesJS JSON
            $table->longText('draft_html')->nullable();
            $table->longText('draft_css')->nullable();
            $table->unsignedInteger('current_revision')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('pagebuilder.storage.database.prefix') . 'pages');
    }
}
