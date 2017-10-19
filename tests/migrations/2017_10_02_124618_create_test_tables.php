<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class CreateTestUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('test_users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        $now = Carbon::now();
        DB::table('test_users')->insert([
            'email' => 'hello@db-exporter.com',
            'password' => Hash::make('123'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('test_users');
    }
}
