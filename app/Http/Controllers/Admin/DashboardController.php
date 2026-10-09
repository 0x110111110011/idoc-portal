<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = [
            'totalUsers' => User::query()->count(),

            'activeUsers' => User::query()
                ->where('is_active', true)
                ->count(),

            'inactiveUsers' => User::query()
                ->where('is_active', false)
                ->count(),

            'totalFolders' => Folder::query()->count(),

            // Soft-deleted documents are excluded by the model scope.
            'totalDocuments' => Document::query()->count(),

            'totalActivities' => ActivityLog::query()->count(),
        ];

        $recentActivities = ActivityLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn(ActivityLog $activity) => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toISOString(),

                'subject_type' => $activity->subject_type
                    ? class_basename($activity->subject_type)
                    : null,

                'subject_id' => $activity->subject_id,

                'user' => $activity->user
                    ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email,
                    ]
                    : null,
            ])
            ->values();

        $recentDocuments = Document::query()
            ->with([
                'folder:id,name',
                'uploader:id,name',
                'currentVersion:id,document_id,version_number,original_name,size',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn(Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'extension' => $document->extension,
                'created_at' => $document->created_at?->toISOString(),

                'folder' => $document->folder
                    ? [
                        'id' => $document->folder->id,
                        'name' => $document->folder->name,
                    ]
                    : null,

                'uploader' => $document->uploader
                    ? [
                        'name' => $document->uploader->name,
                    ]
                    : null,

                'current_version' => $document->currentVersion
                    ? [
                        'version_number'
                            => $document->currentVersion->version_number,
                        'original_name'
                            => $document->currentVersion->original_name,
                        'size' => $document->currentVersion->size,
                    ]
                    : null,
            ])
            ->values();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
