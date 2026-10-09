<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Throwable;

class UserAuthActivitySubscriber
{
    public function handleUserLogin(Login $event): void
    {
        if (
            $event->guard !== 'web'
            || ! ($event->user instanceof User)
        ) {
            return;
        }

        $this->record(
            user: $event->user,
            action: 'auth.login',
            description: 'User logged in successfully.',
        );
    }

    public function handleUserLogout(Logout $event): void
    {
        if (
            $event->guard !== 'web'
            || ! ($event->user instanceof User)
        ) {
            return;
        }

        $this->record(
            user: $event->user,
            action: 'auth.logout',
            description: 'User logged out.',
        );
    }

    private function record(
        User $user,
        string $action,
        string $description,
    ): void {
        $request = app()->bound('request')
            ? app('request')
            : null;

        if (! ($request instanceof Request)) {
            $request = null;
        }

        try {
            app(ActivityLogService::class)->record(
                action: $action,
                subject: $user,
                description: $description,
                properties: [
                    'guard' => 'web',
                ],
                user: $user,
                request: $request,
            );
        } catch (Throwable $exception) {
            // Authentication must remain usable if audit logging fails.
            report($exception);
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleUserLogin',
            Logout::class => 'handleUserLogout',
        ];
    }
}
