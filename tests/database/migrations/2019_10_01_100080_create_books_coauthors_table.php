<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


/**
 * Class Posts
 */
class CreateBooksCoauthorsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books_coauthors', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('book_id');
            $table->integer('coauthor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('books_coauthors');
    }

}
