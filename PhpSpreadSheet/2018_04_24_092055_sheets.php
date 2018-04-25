<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Sheets extends Migration
{
    public function up()
    {
      Schema::create('sheets',
        function (Blueprint $table)
        {
          $table->bigIncrements('id');
          $table->string('sheetname', 255);
          $table->string('col_no', 10);
          $table->string('row_no', 10);
          $table->string('value', 255);
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
      Schema::drop('sheets');
    }
}
