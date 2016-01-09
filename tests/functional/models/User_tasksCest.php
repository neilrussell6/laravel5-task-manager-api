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
    | Because the following test core Eloquent behaviour, they are not strictly
    | necessary during actual development. They are here purely to demo some
    | useful eloquent techniques.
    |
    */

    /** @test */
    public function demo_inefficient_eloquent_relationship_query(FunctionalTester $I)
    {
        // given ... 2 users, each with their own tasks

        // when ... we get all users
        $users = User::all();
        $I->assertSame(2, count($users));

        //      ... and loop through them, calling the tasks method on each user without either of the eager loading methods ("with" or "load")
        //          it would result in a new SQL query for each user's tasks (which is inefficient, especially for large data sets, so use eager loading instead)
        $user_tasks = [];
        foreach ($users as $user) {
            $_user_tasks = $user->tasks()->get();
            array_push($user_tasks, $_user_tasks);
        }

        // then ... should have 2 tasks for user1 & 1 for user2
        $I->assertSame(2, count($user_tasks[0])); // user1's tasks
        $I->assertSame(1, count($user_tasks[1])); // user2's tasks
    }

    /** @test */
    public function demo_efficient_eager_loading_eloquent_relationship_query(FunctionalTester $I)
    {
        // given ... 2 users, each with their own tasks

        // when ... we get users "with" their tasks
        //          it would result in only 2 SQL queries in total (which is efficient)
        $users = User::with('tasks')->get();

        // then ... should return all users with their tasks
        $I->assertSame(2, count($users));
        $I->assertSame(2, count($users[0]->tasks)); // user 1
        $I->assertSame(1, count($users[1]->tasks)); // user 2
    }

    /** @test */
    public function demo_efficient_lazy_eager_loading_eloquent_relationship_query(FunctionalTester $I)
    {
        // given ... 2 users, each with their own tasks

        // when ... we get all users
        $users = User::all();

        // then ... should return all users
        $I->assertSame(2, count($users));

        // when ... we then "load" tasks for all users
        //          it would still result in only 2 SQL queries in total (which is efficient)
        //          this achieves lazy version of previous test, which allows us to conditionally load user's tasks after loading all users
        $users->load('tasks');

        // then ... should attach each user's tasks
        $I->assertSame(2, count($users[0]->tasks)); // user 1
        $I->assertSame(1, count($users[1]->tasks)); // user 2
    }
}
