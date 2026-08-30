<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Tasks\Entities\NotificationUser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Blade::if('canuser', function($key) {
            return canUser($key);
        });

        // Make unread count available on ALL views
        view()->composer('*', function ($view) {
            if (Auth::check()) {

                $unreadCount = NotificationUser::where('user_id', Auth::id())
                    ->where('is_read', 0)
                    ->count();

                $view->with('unreadNotificationsCount', $unreadCount);
            }
        });
    }
}
