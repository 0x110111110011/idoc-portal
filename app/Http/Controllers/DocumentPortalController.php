<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DocumentVersionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentPortalController extends Controller
{
    public function __construct(
        private readonly DocumentVersionService $versionService,
        private readonly ActivityLogService $activityLogService,
    ) {
        //
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Folder::query()
            ->with('creator:id,name,email')
            ->withCount('documents');

        if (! $user->isAdmin()) {
            $query->where(function (Builder $query) use ($user) {
                $query
                    ->where('created_by', $user->id)
                    ->orWhereHas('users', function (Builder $query) use ($user) {
                        $query->where('users.id', $user->id);
                    });
            });
        }

        $folders = $query
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Folder $folder) use ($user) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'description' => $folder->description,
                    'documents_count' => $folder->documents_count,
                    'created_at' => $folder->created_at?->toISOString(),

                    'creator' => $folder->creator
                        ? ['name' => $folder->creator->name]
                        : null,

                    'can_edit' => $user->can('update', $folder),
                    'can_delete' => $user->can('delete', $folder),
                ];
            });

        return Inertia::render('portal/index', [
            'folders' => $folders,
            'canCreateFolder' => $user->can('create', Folder::class),
            'flash' => [
                'success' => session('success'),
                'warning' => session('warning'),
            ],
        ]);
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        $this->authorize('create', Folder::class);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:folders,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $folder = Folder::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogService->record(
            action: 'folder.created',
            subject: $folder,
            description: "Folder '{$folder->name}' was created.",
            properties: [
                'folder_id' => $folder->id,
                'name' => $folder->name,
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.index')
            ->with('success', 'Folder created successfully.');
    }

    public function showFolder(
        Request $request,
        Folder $folder,
    ): Response {
        $this->authorize('view', $folder);

        $user = $request->user();

        $canEdit = $user->can('editContents', $folder);
        $canManageAccess = $user->can('manageAccess', $folder);

        $folder->load('creator:id,name,email');

        $documents = $folder->documents()
            ->with([
                'uploader:id,name,email',
                'currentVersion.uploader:id,name,email',
                'versions' => fn($query) => $query
                    ->with('uploader:id,name,email')
                    ->orderByDesc('version_number'),
            ])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn(Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'extension' => $document->extension,
                'created_at' => $document->created_at?->toISOString(),
                'updated_at' => $document->updated_at?->toISOString(),

                'uploader' => $document->uploader
                    ? [
                        'name' => $document->uploader->name,
                    ]
                    : null,

                'current_version' => $this->versionPayload(
                    $document->currentVersion
                ),

                'versions' => $document->versions
                    ->map(
                        fn(DocumentVersion $version)
                        => $this->versionPayload($version)
                    )
                    ->values(),
            ]);

        $sharedUsers = $folder->users()
            ->orderBy('users.name')
            ->get([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->map(fn(User $sharedUser) => [
                'id' => $sharedUser->id,
                'name' => $sharedUser->name,
                'email' => $sharedUser->email,
                'access' => $sharedUser->pivot->access,
            ])
            ->values();

        $availableUsers = $canManageAccess
            ? User::query()
                ->where('is_active', true)
                ->where('role', UserRole::USER->value)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn(User $availableUser) => [
                    'id' => $availableUser->id,
                    'name' => $availableUser->name,
                    'email' => $availableUser->email,
                ])
                ->values()
            : [];

        return Inertia::render('portal/folders/show', [
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'description' => $folder->description,
                'created_by' => $folder->created_by,
                'creator' => $folder->creator
                    ? [
                        'name' => $folder->creator->name,
                    ]
                    : null,
            ],

            'documents' => $documents,
            'sharedUsers' => $sharedUsers,
            'availableUsers' => $availableUsers,

            'permissions' => [
                'canEdit' => $canEdit,
                'canManageAccess' => $canManageAccess,
            ],

            'flash' => [
                'success' => session('success'),
                'warning' => session('warning'),
            ],
        ]);
    }

    public function storeDocument(
        Request $request,
        Folder $folder,
    ): RedirectResponse {
        $this->authorize('editContents', $folder);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,png,jpg,jpeg',
            ],
        ]);

        $file = $data['file'];

        $document = $folder->documents()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'extension' => strtolower($file->extension() ?: 'bin'),
            'uploaded_by' => $request->user()->id,
        ]);

        try {
            $version = $this->versionService->createInitialVersion(
                $document,
                $file,
                $request->user()->id,
            );
        } catch (Throwable $exception) {
            // The initial version failed, so remove the incomplete record.
            $document->forceDelete();

            throw $exception;
        }

        $this->activityLogService->record(
            action: 'document.uploaded',
            subject: $document,
            description: "Document '{$document->title}' was uploaded.",
            properties: [
                'folder_id' => $folder->id,
                'document_id' => $document->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'size' => $version->size,
                'checksum' => $version->checksum,
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.folders.show', $folder)
            ->with('success', 'Document uploaded successfully.');
    }

    public function shareFolder(
        Request $request,
        Folder $folder,
    ): RedirectResponse {
        $this->authorize('manageAccess', $folder);

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn($query) => $query
                        ->where('is_active', true)
                        ->where('role', UserRole::USER->value)
                ),
            ],
            'access' => [
                'required',
                Rule::in(['view', 'edit']),
            ],
        ]);

        $targetUser = User::query()->findOrFail($data['user_id']);

        $folder->users()->syncWithoutDetaching([
            $targetUser->id => [
                'access' => $data['access'],
            ],
        ]);

        $this->activityLogService->record(
            action: 'folder.access_updated',
            subject: $folder,
            description: "Folder access for {$targetUser->email} was updated.",
            properties: [
                'folder_id' => $folder->id,
                'target_user_id' => $targetUser->id,
                'access' => $data['access'],
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.folders.show', $folder)
            ->with('success', 'Folder access updated.');
    }

    public function removeShare(
        Request $request,
        Folder $folder,
        User $user,
    ): RedirectResponse {
        $this->authorize('manageAccess', $folder);

        $removed = $folder->users()->detach($user->id);

        if ($removed > 0) {
            $this->activityLogService->record(
                action: 'folder.access_revoked',
                subject: $folder,
                description: "Folder access for {$user->email} was revoked.",
                properties: [
                    'folder_id' => $folder->id,
                    'target_user_id' => $user->id,
                ],
                user: $request->user(),
                request: $request,
            );
        }

        return to_route('portal.folders.show', $folder)
            ->with('success', 'Folder sharing updated.');
    }

    public function uploadVersion(
        Request $request,
        Document $document,
    ): RedirectResponse {
        $this->authorize('uploadVersion', $document);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,png,jpg,jpeg',
            ],
        ]);

        $version = $this->versionService->createVersion(
            $document,
            $data['file'],
            $request->user()->id,
        );

        $this->activityLogService->record(
            action: 'document.version_uploaded',
            subject: $document,
            description: "Version {$version->version_number} was uploaded.",
            properties: [
                'document_id' => $document->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'size' => $version->size,
                'checksum' => $version->checksum,
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.folders.show', $document->folder_id)
            ->with('success', 'New document version uploaded.');
    }

    public function download(
        Request $request,
        Document $document,
    ): StreamedResponse {
        $this->authorize('download', $document);

        $version = $document->currentVersion;

        abort_unless($version, 404, 'No current document version exists.');

        return $this->downloadVersionFile(
            $request,
            $document,
            $version,
            'document.downloaded',
            true,
        );
    }

    public function downloadVersion(
        Request $request,
        Document $document,
        DocumentVersion $version,
    ): StreamedResponse {
        abort_unless(
            $version->document_id === $document->id,
            404,
        );

        $this->authorize('download', $document);

        return $this->downloadVersionFile(
            $request,
            $document,
            $version,
            'document.version_downloaded',
            false,
        );
    }

    public function preview(
        Request $request,
        Document $document,
    ): StreamedResponse {
        $this->authorize('view', $document);

        $version = $document->currentVersion;

        abort_unless($version, 404, 'No current document version exists.');

        // Only render safe document types inline.
        abort_unless(
            in_array($version->mime_type, [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ], true),
            415,
            'Preview is available only for PDF and common image documents.',
        );

        $disk = Storage::disk('local');

        abort_unless($disk->exists($version->stored_path), 404);

        $this->activityLogService->record(
            action: 'document.previewed',
            subject: $document,
            description: "Document '{$document->title}' was previewed.",
            properties: [
                'document_id' => $document->id,
                'version_id' => $version->id,
            ],
            user: $request->user(),
            request: $request,
        );

        return $disk->response(
            $version->stored_path,
            $version->original_name,
            [
                'Content-Type' => $version->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'Cache-Control' => 'private, no-store',
            ],
            'inline',
        );
    }

    private function downloadVersionFile(
        Request $request,
        Document $document,
        DocumentVersion $version,
        string $action,
        bool $isCurrent,
    ): StreamedResponse {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($version->stored_path), 404);

        $this->activityLogService->record(
            action: $action,
            subject: $document,
            description: "Document '{$document->title}' was downloaded.",
            properties: [
                'document_id' => $document->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'is_current_version' => $isCurrent,
            ],
            user: $request->user(),
            request: $request,
        );

        return $disk->download(
            $version->stored_path,
            $version->original_name,
            [
                'Content-Type' => $version->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function versionPayload(
        ?DocumentVersion $version,
    ): ?array {
        if (! $version) {
            return null;
        }

        return [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'original_name' => $version->original_name,
            'mime_type' => $version->mime_type,
            'size' => $version->size,
            'created_at' => $version->created_at?->toISOString(),
            'uploader' => $version->uploader
                ? [
                    'name' => $version->uploader->name,
                ]
                : null,
        ];
    }
}
