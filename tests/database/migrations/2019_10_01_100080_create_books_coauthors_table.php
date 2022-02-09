<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
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
            $table->integer('percentage')->nullable()->default(null);
            $table->nullableOwnerships();
            $table->nullableTimestamps();
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

};
