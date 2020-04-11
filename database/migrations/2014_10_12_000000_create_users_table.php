<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('header');
            $table->string('Link_post')->unique();
            $table->text('text')->nullable();
            $table->string('Link_img')->nullable();
            $table->string('Link_video')->nullable();
            $table->string('Link_audio')->nullable();
            $table->string('Link_silent_video')->nullable();
            $table->string('Link_gif')->nullable();
            $table->string('Link_gfycat')->nullable();
            $table->boolean('was_posted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post');
    }
}
