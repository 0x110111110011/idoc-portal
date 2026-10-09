<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Folder;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Ssr\Gateway;
use App\Models\ActivityLog;
use App\Policies\ActivityLogPolicy;
use App\Listeners\UserAuthActivitySubscriber;
use Illuminate\Support\Facades\Event;

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
        Gate::policy(Folder::class, FolderPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Event::subscribe(UserAuthActivitySubscriber::class);
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

        Password::defaults(
            fn(): ?Password => app()->isProduction()
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
