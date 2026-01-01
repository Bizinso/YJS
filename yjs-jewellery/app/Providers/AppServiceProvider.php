<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Activity::saving(function (Activity $activity) {
            
            if (Auth::user()) {
                $agent = new Agent();

                $activity->causer_id = Auth::id();
                $activity->causer_type = 'App\\Models\\User';
                $activity->device_platform = $agent->platform();
                $activity->device_type = $agent->device();
                $activity->device_browser = $agent->browser();
                $activity->ip_address = request()->ip();
                $activity->event = $activity->event ?? 'updated'; // optional
            }
        });
    }
}
