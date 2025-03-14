<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackTable extends Migration
{
  /**
  * Run the migrations.
  *
  * @return void
  */
  public function up()
  {
    Schema::create('feedback', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->text('texto');
      $table->integer('sugestao_id');
      $table->integer('user_id');
      // $table->foreign('sugestao_id')->references('id')->on('sugestaos')->onDelete('cascade');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
    Schema::dropIfExists('feedback');
  }
}
