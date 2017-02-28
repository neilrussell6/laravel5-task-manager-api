<?php namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
//        User::class => 'App\Policies\UserPolicy',
//        Project::class => 'App\Policies\ProjectPolicy',
//        Task::class => 'App\Policies\TaskPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot ()
    {
        $this->registerPolicies();
    }
}
