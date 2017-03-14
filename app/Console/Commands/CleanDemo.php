<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CleanDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all projects and task created by demo user and re-seed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get demo user
        $demo_user = User::where('username', 'demo')->first();

        // delete all of user's projects (tasks will also be deleted)
        $demo_user->projects->each(function($project) {
            $project->delete();
        });

        // re-seed
        Artisan::call('db:seed', ['--class' => 'DatabaseDemoSeeder', '--force' => true]);
    }
}
