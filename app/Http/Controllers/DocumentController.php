<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Services\ActivityLogService;
use App\Services\DocumentVersionService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentVersionService $versionService,
        private readonly ActivityLogService $activityLogService,
    ) {
        //
    }

    public function index(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $documents = $folder->documents()
            ->with([
                'currentVersion:id,document_id,version_number,original_name,size,mime_type,uploaded_by,created_at',
                'uploader:id,name,email',
            ])
            ->latest()
            ->paginate(20);

        return response()->json($documents);
    }

    public function store(
        StoreDocumentRequest $request,
        Folder $folder,
    ): JsonResponse {
        $this->authorize('editContents', $folder);

        $file = $request->file('file');

        $document = $folder->documents()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'extension' => strtolower(
                $file->getClientOriginalExtension()
            ),
            'uploaded_by' => $request->user()->id,
        ]);

        $version = $this->versionService->createInitialVersion(
            $document,
            $file,
            $request->user()->id,
        );

        $this->activityLogService->record(
            action: 'document.uploaded',
            subject: $document,
            description: "Document '{$document->title}' was uploaded.",
            properties: [
                'document_id' => $document->id,
                'folder_id' => $folder->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'original_name' => $version->original_name,
                'size' => $version->size,
                'mime_type' => $version->mime_type,
                'checksum' => $version->checksum,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => $document->fresh([
                'folder',
                'currentVersion',
                'uploader',
            ]),
        ], 201);
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'data' => $document->load([
                'folder',
                'currentVersion',
                'versions.uploader:id,name,email',
                'uploader:id,name,email',
            ]),
        ]);
    }

    public function update(
        StoreDocumentRequest $request,
        Document $document,
    ): JsonResponse {
        $this->authorize('update', $document);

        $oldValues = $document->only([
            'title',
            'description',
        ]);

        $document->update([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
        ]);

        $this->activityLogService->record(
            action: 'document.updated',
            subject: $document,
            description: "Document '{$document->title}' was updated.",
            properties: [
                'before' => $oldValues,
                'after' => $document->only([
                    'title',
                    'description',
                ]),
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Document updated successfully.',
            'data' => $document->fresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Document $document,
    ): JsonResponse {
        $this->authorize('delete', $document);

        $title = $document->title;
        $documentId = $document->id;

        $document->delete();

        $this->activityLogService->record(
            action: 'document.deleted',
            subject: $document,
            description: "Document '{$title}' was deleted.",
            properties: [
                'document_id' => $documentId,
                'title' => $title,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    public function download(
        Request $request,
        Document $document,
    ): StreamedResponse {
        $this->authorize('download', $document);

        $version = $document->currentVersion;

        abort_unless(
            $version,
            404,
            'Document version not found.',
        );

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($version->stored_path),
            404,
            'File not found.',
        );

        $this->activityLogService->record(
            action: 'document.downloaded',
            subject: $document,
            description: "Document '{$document->title}' was downloaded.",
            properties: [
                'document_id' => $document->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'original_name' => $version->original_name,
            ],
            user: $request->user(),
            request: $request,
        );

        return $disk->download(
            $version->stored_path,
            $version->original_name,
            [
                'Content-Type' => $version->mime_type,
            ]
        );
    }

    public function uploadVersion(
        UploadDocumentVersionRequest $request,
        Document $document,
    ): JsonResponse {
        $version = $this->versionService->createVersion(
            $document,
            $request->file('file'),
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
                'original_name' => $version->original_name,
                'size' => $version->size,
                'mime_type' => $version->mime_type,
                'checksum' => $version->checksum,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'New document version uploaded successfully.',
            'data' => $version,
        ], 201);
    }
}
