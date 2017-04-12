<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Generator;
use Illuminate\Support\Facades\App;

class ProjectTableSeeder extends Seeder
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

            $demo_user = User::where('username', 'demo')->first();

            // a few projects for each user (excluding demo user)
            User::where('id', '!=', $demo_user['id'])->each(function($user) use ($demo_user) {
                factory(Project::class, $this->faker->numberBetween(1, 3))->create(['user_id' => $user['id']]);
            });
        }
    }
}
