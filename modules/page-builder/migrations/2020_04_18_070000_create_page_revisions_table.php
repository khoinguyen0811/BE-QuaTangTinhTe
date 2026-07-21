<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePageRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('pagebuilder.storage.database.prefix') . 'page_revisions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id');
            $table->unsignedInteger('revision');
            $table->longText('project_json');
            $table->longText('html');
            $table->longText('css');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('page_id')
                ->references('id')
                ->on(config('pagebuilder.storage.database.prefix') . 'pages')
                ->onDelete('cascade');
            
            $table->index(['page_id', 'revision']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('pagebuilder.storage.database.prefix') . 'page_revisions');
    }
}
