<?php

use Illuminate\Database\Seeder;

class UserWithTasksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create 1 task for each of the 3 users
        //factory(\App\User::class, 3)->create()->each(
        //    function($user) {
        //        $task = factory(\App\Task::class)->make();
        //        $user->tasks()->save($task);
        //    }
        //);

        // create 5 tasks for each of the 3 users
        factory(\App\User::class, 3)->create()->each(
            function($user) {
                $tasks = factory(\App\Task::class, 5)->make();
                $user->tasks()->saveMany($tasks);
            }
        );

        // update user 1 email
        $user_table = with(new \App\User)->getTable();
        DB::table($user_table)
            ->where("id", 1)
            ->update(["email" => "aaa@aaa.com"]);
    }
}
