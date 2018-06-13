<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShibbolethUserGroupsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_groups')) {
          Schema::create('user_groups', function (Blueprint $table) {
              $table->increments('id');
              $table->string('name', 30)->unique();
              $table->text('description');
              $table->timestamps();
          });

          DB::table('user_groups')->insert(array(
              'name' => 'default',
          ));
        } else {

        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_groups');
    }

}
