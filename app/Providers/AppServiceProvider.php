<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogLoginSession;
use App\Listeners\LogLogout;
use App\Listeners\LogPasswordResetComplete;
use App\Listeners\LogPasswordResetRequest;
use App\Models\User;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity as ActivityLogEntry;

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
        $this->configureDefaults();
        $this->registerEventListeners();
        
        // Register observers
        User::observe(UserObserver::class);
    }

    /**
     * Register event listeners for login/logout tracking
     */
    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, LogLoginSession::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Logout::class, LogLogout::class);
        Event::listen(PasswordResetLinkSent::class, LogPasswordResetRequest::class);
        Event::listen(PasswordReset::class, LogPasswordResetComplete::class);

        ActivityLogEntry::saved(function (ActivityLogEntry $activity): void {
            if (app()->runningInConsole()) {
                return;
            }

            $properties = $activity->properties?->toArray() ?? [];

            if (array_key_exists('ip_address', $properties) && !empty($properties['ip_address'])) {
                return;
            }

            $ipAddress = request()?->ip() ?? request()?->getClientIp() ?? $activity->ip_address;

            if (!$ipAddress) {
                return;
            }

            $properties['ip_address'] = $ipAddress;
            $activity->properties = $properties;

            if (empty($activity->ip_address)) {
                $activity->ip_address = $ipAddress;
            }

            $activity->saveQuietly();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
