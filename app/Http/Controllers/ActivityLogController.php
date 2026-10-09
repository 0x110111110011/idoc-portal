<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    private const ACTIONS = [
        'auth.login' => 'User login',
        'auth.logout' => 'User logout',
        'admin.user_created' => 'User created',
        'admin.user_updated' => 'User updated',
        'folder.created' => 'Folder created',
        'folder.updated' => 'Folder updated',
        'folder.deleted' => 'Folder deleted',
        'folder.access_updated' => 'Folder access updated',
        'folder.access_revoked' => 'Folder access revoked',
        'document.uploaded' => 'Document uploaded',
        'document.updated' => 'Document updated',
        'document.downloaded' => 'Document downloaded',
        'document.version_downloaded' => 'Previous version downloaded',
        'document.previewed' => 'Document previewed',
        'document.version_uploaded' => 'Document version uploaded',
        'document.deleted' => 'Document deleted',
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ActivityLog::class);

        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'action' => [
                'nullable',
                Rule::in(array_keys(self::ACTIONS)),
            ],
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ]);

        $search = trim($filters['search'] ?? '');

        $query = ActivityLog::query()
            ->with('user:id,name,email');

        if ($search !== '') {
            $pattern = "%{$search}%";

            $query->where(function (Builder $query) use ($pattern) {
                $query
                    ->where('action', 'ilike', $pattern)
                    ->orWhere('description', 'ilike', $pattern)
                    ->orWhereHas(
                        'user',
                        fn(Builder $userQuery) => $userQuery
                            ->where('name', 'ilike', $pattern)
                            ->orWhere('email', 'ilike', $pattern),
                    );
            });
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                $filters['date_from'] . ' 00:00:00',
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                $filters['date_to'] . ' 23:59:59.999999',
            );
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(function (ActivityLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'subject_type' => $this->subjectLabel(
                        $log->subject_type,
                    ),
                    'subject_id' => $log->subject_id,
                    'properties' => $log->properties,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toISOString(),

                    'user' => $log->user
                        ? [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'email' => $log->user->email,
                        ]
                        : null,
                ];
            });

        return Inertia::render('admin/activity-logs/index', [
            'logs' => $logs,

            'filters' => [
                'search' => $search,
                'action' => $filters['action'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],

            'actions' => collect(self::ACTIONS)
                ->map(fn(string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function subjectLabel(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return match ($type) {
            Folder::class => 'Folder',
            Document::class => 'Document',
            DocumentVersion::class => 'Document version',
            User::class => 'User',
            default => class_basename($type),
        };
    }
}
