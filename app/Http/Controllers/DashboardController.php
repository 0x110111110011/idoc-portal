<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $accessibleFolders = Folder::query()
            ->accessibleTo($user);

        $accessibleDocuments = Document::query()
            ->whereHas('folder', function (Builder $query) use ($user) {
                $query->accessibleTo($user);
            });

        $stats = [
            'accessibleFolders' => (clone $accessibleFolders)->count(),

            'accessibleDocuments' => (clone $accessibleDocuments)->count(),
        ];

        $folders = (clone $accessibleFolders)
            ->withCount('documents')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get([
                'id',
                'name',
                'description',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->map(fn(Folder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'description' => $folder->description,
                'documents_count' => $folder->documents_count,
                'updated_at' => $folder->updated_at?->toISOString(),
            ])
            ->values();

        $recentDocuments = (clone $accessibleDocuments)
            ->with([
                'folder:id,name',
                'uploader:id,name',
                'currentVersion:id,document_id,version_number,original_name,size',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn(Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'extension' => $document->extension,
                'updated_at' => $document->updated_at?->toISOString(),

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

        /*
         * Privacy decision: show this user their own actions only.
         * Do not expose the global activity feed on a regular user dashboard.
         */
        $recentActivities = ActivityLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get([
                'id',
                'action',
                'description',
                'created_at',
            ])
            ->map(fn(ActivityLog $activity) => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toISOString(),
            ])
            ->values();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'folders' => $folders,
            'recentDocuments' => $recentDocuments,
            'recentActivities' => $recentActivities,
        ]);
    }
}
