<?php

use Gecche\Breeze\Facades\Schema;
use Gecche\Breeze\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class Posts
 */
class CreateAuthorsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code',6)->unique();
            $table->string('name');
            $table->string('surname');
            $table->string('nation');
            $table->date('birthdate');
            $table->timestamps();
            $table->ownerships();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('authors');
    }

}
