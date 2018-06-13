<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShibbolethUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if(!Schema::hasTable('auth_types')) {

            Schema::create('auth_types', function(Blueprint $table) {

                $table->increments('id');
                $table->string('name');
                $table->softDeletes();

            });

            DB::table('auth_types')->insert(array(
                'name' => 'local',
            ));

            DB::table('auth_types')->insert(array(
                'name' => 'shibboleth',
            ));
        }

        if (!Schema::hasTable('users')) {

            Schema::create('users', function (Blueprint $table) {

                $table->increments('id');
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->unsignedInteger('auth_type');
                $table->string('password', 60)->nullable()->default(null);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();

                $table->index('first_name');
                $table->index('last_name');

                $table->foreign('auth_type', 'auth_type_fk')->references('id')->on('auth_types');

            });

        } else {

            Schema::table('users', function (BluePrint $table) {

                $columns = $table->getColumns();

                if (is_null($columns->get('email'))) {

                    $table->string('email')->unique();

                }

                if (is_null($columns->get('first_name'))) {

                    $table->string('first_name');
                    $table->index('first_name');

                }

                if (is_null($columns->get('last_name'))) {

                    $table->string('last_name');
                    $table->index('last_name');

                }

                if (is_null($columns->get('auth_type'))) {

                    $table->unsignedInteger('auth_type');
                    $table->foreign('auth_type', 'auth_type_fk')->references('id')->on('auth_types');

                }

                if (is_null($columns->get('password'))) {
                    $table->string('password', 60)->nullable()->default(null);
                }

                if (is_null($columns->get('created_at'))) {
                    $table->timestamp('created_at', 0)->nullable();
                }

                if (is_null($columns->get('upated_at'))) {
                    $table->timestamp('upated_at', 0)->nullable();
                }

                if (is_null($columns->get('deleted_at'))) {
                    $table->timestamp('deleted_at', 0)->nullable();
                }

            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('auth_types');
    }
}
