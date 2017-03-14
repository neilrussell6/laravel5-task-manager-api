<?php namespace App\Console;

use App\Console\Commands\CleanDemo;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CleanDemo::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule (Schedule $schedule)
    {
        $schedule->command('demo:clean')
            ->everyTenMinutes()
            ->when(function() {
                $demo_user = User::where('username', 'demo')->first();
                $demo_project_count = $demo_user->projects->count();
                $demo_task_count = $demo_user->tasks->count();

                $default_demo_project_count = intval(getenv('DEFAULT_DEMO_PROJECT_COUNT'));
                $default_demo_task_count = intval(getenv('DEFAULT_DEMO_TASK_PER_PROJECT_COUNT')) * $default_demo_project_count;

                return $demo_project_count !== $default_demo_project_count || $demo_task_count !== $default_demo_task_count;
            })
            ->appendOutputTo(storage_path('logs/demo_clean.log'));
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands ()
    {
        require base_path('routes/console.php');
    }
}
