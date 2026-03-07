<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\PersonalDataSheet;
use App\Models\User;
use App\Policies\OfficePolicy;
use App\Policies\PersonalDataSheetPolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureActivityLoggingListeners();
        $this->configurePolicies();
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

    protected function configurePolicies(): void
    {
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(PersonalDataSheet::class, PersonalDataSheetPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // super_admin bypasses all policy gates
        Gate::before(fn (User $user): ?bool => $user->hasRole('super_admin') ? true : null);
    }

    /**
     * Configure authentication activity log listeners.
     */
    protected function configureActivityLoggingListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (! Schema::hasTable('activity_log')) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'email' => $event->user->email,
                    'ip' => request()->ip(),
                ])
                ->log('user.logged_in');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user || ! Schema::hasTable('activity_log')) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'email' => $event->user->email,
                    'ip' => request()->ip(),
                ])
                ->log('user.logged_out');
        });

        Event::listen(Registered::class, function (Registered $event): void {
            if (! Schema::hasTable('activity_log')) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'email' => $event->user->email,
                    'ip' => request()->ip(),
                ])
                ->log('user.registered');
        });
    }
}
