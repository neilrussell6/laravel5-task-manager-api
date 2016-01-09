<?php

use App\Task;
use App\User;

class Task_userCest
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
    public function should_return_the_user_that_the_task_belongs_to(FunctionalTester $I)
    {
        // given ... task that belongs to user 1
        $user = User::find(1);
        $I->assertSame('Sherlock Holmes', $user['name']);
        $task = Task::where('name', "solve the crime")->first();
        $I->assertSame($user['id'], intval($task['user_id']));

        // when ... we call get user on that task
        $task_user = $task->user()->first();

        // then ... should return user 1
        $I->assertSame("Sherlock Holmes", $task_user['name']);
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
        // given ... 3 tasks, each belonging to a user

        // when ... we get all tasks
        $tasks = Task::all();
        $I->assertSame(3, count($tasks));

        //      ... and loop through them, calling the user method on each task without either of the eager loading methods ("with" or "load")
        //          it would result in a new SQL query for each task's user (which is inefficient, especially for large data sets, so use eager loading instead)
        $task_users = [];
        foreach ($tasks as $task) {
            $task_user = $task->user()->first(); // we need to call "first" even on a "x to one" relationship like "belongsTo", because "get" will return an array
            array_push($task_users, $task_user);
        }

        // then ... first 2 tasks should belong to user1 & 3rd task should belong to user2
        $I->assertSame("Sherlock Holmes", $task_users[0]['name']); // task 1
        $I->assertSame("Sherlock Holmes", $task_users[1]['name']); // task 2
        $I->assertSame("Dr Watson", $task_users[2]['name']); // task 3
    }

    /** @test */
    public function demo_efficient_eager_loading_eloquent_relationship_query(FunctionalTester $I)
    {
        // given ... 3 tasks, each belonging to a user

        // when ... we get tasks "with" their user
        //          it would result in only 2 SQL queries in total (which is efficient)
        $tasks = Task::with('user')->get();

        // then ... should return all tasks with their user
        $I->assertSame(3, count($tasks));
        $I->assertSame("Sherlock Holmes", $tasks[0]->user['name']); // task 1
        $I->assertSame("Sherlock Holmes", $tasks[1]->user['name']); // task 2
        $I->assertSame("Dr Watson", $tasks[2]->user['name']); // task 3
    }

    /** @test */
    public function demo_efficient_lazy_eager_loading_eloquent_relationship_query(FunctionalTester $I)
    {
        // given ... 3 tasks, each belonging to a user

        // when ... we get all tasks
        $tasks = Task::all();

        // then ... should return all tasks
        $I->assertSame(3, count($tasks));

        // when ... we then "load" users for all tasks
        //          it would still result in only 2 SQL queries in total (which is efficient)
        //          this achieves lazy version of previous test, which allows us to conditionally load task's user after loading all tasks
        $tasks->load('user');

        // then ... should attach each task's user
        $I->assertSame("Sherlock Holmes", $tasks[0]->user['name']); // task 1
        $I->assertSame("Sherlock Holmes", $tasks[1]->user['name']); // task 2
        $I->assertSame("Dr Watson", $tasks[2]->user['name']); // task 3
    }
}
