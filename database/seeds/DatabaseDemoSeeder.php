<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $demo_user = User::where('username', 'demo')->first();

        // create 2 projects
        $projects = factory(Project::class, 2)->create(['user_id' => $demo_user->id]);

        // and create 5 tasks for each project
        $projects->each(function($project) use ($demo_user) {
            factory(Task::class, 5)->create(['project_id' => $project['id'], 'user_id' => $demo_user->id]);
        });
    }
}
