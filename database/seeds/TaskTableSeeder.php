<?php

use App\Models\Task;
use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class TaskTableSeeder extends Seeder
{
    protected $faker;

    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (App::environment() === 'local') {

            $admin_user = User::where('username', 'administrator')->first();
            $demo_user = User::where('username', 'demo')->first();

            // a few tasks for each of admin user's projects
            User::find($admin_user['id'])->projects()->get()->each(function($project) {
                factory(Task::class, $this->faker->numberBetween(5, 10))->create(['project_id' => $project['id'], 'user_id' => 1]);
            });

            // and a few tasks for each of the other user's projects (excluding demo user)
            User::where('id', '!=', [ $admin_user['id'], $demo_user['id'] ])->each(function($user) {
                $user->projects()->get()->each(function($project) use ($user) {
                    factory(Task::class, $this->faker->numberBetween(2, 5))->create(['project_id' => $project['id'], 'user_id' => $user['id']]);
                });
            });
        }
    }
}
