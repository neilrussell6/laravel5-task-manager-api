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

        // create projects
        $projects = factory(Project::class, intval(getenv('DEFAULT_DEMO_PROJECT_COUNT')))->create(['user_id' => $demo_user->id]);

        // and create tasks for each project
        $projects->each(function($project) use ($demo_user) {
            factory(Task::class, intval(getenv('DEFAULT_DEMO_TASK_PER_PROJECT_COUNT')))->create(['project_id' => $project['id'], 'user_id' => $demo_user->id]);
        });
    }
}
