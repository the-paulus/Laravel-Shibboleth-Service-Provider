<?php

use Illuminate\Database\Migrations\Migration;

class CreateShibbolethUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('users')) {

            Schema::create('users', function ($table) {
                $table->increments('id');

                $table->string('email')->unique();
                $table->string('first_name');
                $table->string('last_name');

                $table->enum('auth_type', array('shibboleth', 'local'))->default('shibboleth');

                $table->string('password', 60)->nullable()->default(null);

                $table->timestamps();
                $table->softDeletes();
            });

        } else {

            Schema::table('users', function (BluePrint $table) {

                $columns = $table->getColumns();

                if (is_null($columns->get('email'))) {
                    $table->string('email')->unique();
                }

                if (is_null($columns->get('first_name'))) {
                    $table->string('first_name');
                }

                if (is_null($columns->get('last_name'))) {
                    $table->string('last_name');
                }

                if (is_null($columns->get('auth_type'))) {
                    $table->enum('auth_type', array('shibboleth', 'local'))->default('shibboleth');
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
    }
}
