<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Post extends Migration
{
  public function up()
  {
    Schema::create('post',
      function (Blueprint $table)
      {
        $table->bigIncrements('id');
        $table->string('title', 255);
        $table->string('description', 255);
      }
    );
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('post');
  }
}
