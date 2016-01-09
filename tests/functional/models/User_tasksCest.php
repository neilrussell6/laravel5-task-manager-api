<?php

use App\Task;
use App\User;

class User_tasksCest
{
    public function _before(FunctionalTester $I)
    {
        // sherlock
        $user1 = factory(User::class, 1)->create([
            'name' => "Sherlock Holmes"
        ]);
        // sherlock's tasks
        factory(Task::class, 1)->create([
            'name' => "solve the crime",
            'user_id' => $user1['id']
        ]);
        factory(Task::class, 1)->create([
            'name' => "deduce something",
            'user_id' => $user1['id']
        ]);

        // watson
        $user2 = factory(User::class, 1)->create([
            'name' => "Dr Watson"
        ]);
        // watson's tasks
        factory(Task::class, 1)->create([
            'name' => "misread a clue",
            'user_id' => $user2['id']
        ]);
    }

    /** @test */
    public function should_return_only_the_users_tasks(FunctionalTester $I)
    {
        // given ... a user with 2 tasks
        $user = User::find(1);
        $I->assertSame('Sherlock Holmes', $user['name']);

        // when ... we call the tasks method on that user
        $tasks = $user->tasks()->get();

        // then ... return only their 2 tasks
        $I->assertSame(2, count($tasks));
        $I->assertSame("solve the crime", $tasks[0]['name']);
        $I->assertSame("deduce something", $tasks[1]['name']);
    }

    /** @test */
    public function should_not_return_another_user_s_tasks(FunctionalTester $I)
    {
        // given ... 2 users, each with their own tasks
        $I->assertSame(2, User::count());
        $user = User::find(1);
        $I->assertSame('Sherlock Holmes', $user['name']);

        // when ... we call tasks method on user1
        $tasks = $user->tasks()->where('user_id', 2)->get();

        // then ... do not return any of user2's tasks
        $I->assertEmpty($tasks);
    }

    /*
    |--------------------------------------------------------------------------
    | Notes
    |--------------------------------------------------------------------------
    |
    | The following tests are not strictly necessary during actual development,
    | they are here purely to demo some useful eloquent techniques.
    |
    */

    /** @test */
    public function calling_with_tasks_on_user_model_should_eager_load_all_tasks_for_all_users(FunctionalTester $I)
    {
        // given ... 2 users, each with their own tasks
        $I->assertSame(2, User::count());

        // when ... we call "with tasks" get on the User model
        $users = User::with('tasks')->get();

        // then ... return all users with their tasks
        $I->assertSame(2, count($users));
        $I->assertSame(2, count($users[0]->tasks));
        $I->assertSame(1, count($users[1]->tasks));

        // or when ... we use the lazy loading version to achieve the same thing
        $users = User::all();
        $users->load('tasks'); // this allows us to conditionally load the tasks after load the users, both approaches result in only 2 SQL queries

        // then ... should get the same results
        $I->assertSame(2, count($users));
        $I->assertSame(2, count($users[0]->tasks));
        $I->assertSame(1, count($users[1]->tasks));

        // lastly ... if we loaded all users using:
        $users = User::all();

        // then ... looped through the users and called the tasks method on each one, without either of the eager loading methods ("with" or "load"):
        //          it would result in a new SQL query for each user (which is inefficient, especially for large data sets, so use eager loading instead)
        foreach ($users as $user) {
            $tasks = $user->tasks();
            $I->assertGreaterThan(0, count($tasks));
        }
    }
}
