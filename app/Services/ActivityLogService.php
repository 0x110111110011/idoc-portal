<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $user = null,
        ?Request $request = null,
    ): ActivityLog {
        $request ??= app()->bound('request')
            ? app('request')
            : null;

        $user ??= $request?->user();

        return ActivityLog::query()->create([
            'user_id' => $user?->id,

            'action' => $action,

            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),

            'description' => $description,

            'properties' => $properties ?: null,

            'ip_address' => $request?->ip(),

            'created_at' => now(),
        ]);
    }
}
