<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePortalDocumentRequest;
use App\Http\Requests\UpdatePortalFolderRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class PortalCrudController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Folder CRUD
    |--------------------------------------------------------------------------
    */

    public function editFolder(Folder $folder): Response
    {
        $this->authorize('update', $folder);

        return Inertia::render('portal/folders/edit', [
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'description' => $folder->description,
            ],
        ]);
    }

    public function updateFolder(
        UpdatePortalFolderRequest $request,
        Folder $folder,
    ): RedirectResponse {
        $before = [
            'name' => $folder->name,
            'description' => $folder->description,
        ];

        $folder->update($request->validated());

        $this->activityLogService->record(
            action: 'folder.updated',
            subject: $folder,
            description: "Folder '{$folder->name}' was updated.",
            properties: [
                'before' => $before,
                'after' => [
                    'name' => $folder->name,
                    'description' => $folder->description,
                ],
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.index')
            ->with('success', 'Folder updated successfully.');
    }

    /**
     * Permanently delete a folder and all documents and versions it contains.
     *
     * This operation is intentionally destructive and must be confirmed
     * by the user in the frontend.
     */
    public function destroyFolder(
        Request $request,
        Folder $folder,
    ): RedirectResponse {
        $this->authorize('delete', $folder);

        $actor = $request->user();
        $folderId = $folder->id;
        $folderName = $folder->name;

        /*
         * Delete database records first inside a transaction.
         * Stored files are removed only after the transaction commits.
         */
        $result = DB::transaction(function () use (
            $request,
            $actor,
            $folder,
            $folderId,
            $folderName,
        ): array {
            $documents = $folder->documents()
                ->withTrashed()
                ->with('versions')
                ->lockForUpdate()
                ->get();

            $storedPaths = [];

            foreach ($documents as $document) {
                foreach ($document->versions as $version) {
                    $storedPaths[] = $version->stored_path;
                }

                // Record the deletion before permanently removing the row.
                $this->activityLogService->record(
                    action: 'document.deleted',
                    subject: $document,
                    description: "Document '{$document->title}' was permanently deleted with folder '{$folderName}'.",
                    properties: [
                        'document_id' => $document->id,
                        'folder_id' => $folderId,
                        'title' => $document->title,
                        'permanent' => true,
                        'deleted_with_folder' => true,
                    ],
                    user: $actor,
                    request: $request,
                );

                /*
                 * Break the circular current_version_id reference.
                 * document_versions.document_id can then cascade-delete.
                 */
                $document->forceFill([
                    'current_version_id' => null,
                ])->saveQuietly();

                $document->forceDelete();
            }

            // Delete folder sharing records and the folder itself.
            $folder->users()->detach();
            $folder->delete();

            $this->activityLogService->record(
                action: 'folder.deleted',
                subject: $folder,
                description: "Folder '{$folderName}' was permanently deleted.",
                properties: [
                    'folder_id' => $folderId,
                    'name' => $folderName,
                    'deleted_documents_count' => $documents->count(),
                ],
                user: $actor,
                request: $request,
            );

            return [
                'paths' => array_values(array_unique($storedPaths)),
                'documents_count' => $documents->count(),
            ];
        });

        /*
         * If file cleanup fails, the database deletion is already committed.
         * Report the failure for operational follow-up; never expose storage
         * paths in the response.
         */
        $disk = Storage::disk('local');

        foreach ($result['paths'] as $path) {
            try {
                if (
                    $disk->exists($path)
                    && ! $disk->delete($path)
                ) {
                    report(new RuntimeException(
                        'Failed to remove a stored document after folder deletion.'
                    ));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return to_route('portal.index')
            ->with(
                'success',
                "Folder deleted. {$result['documents_count']} document record(s) were permanently removed."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Document CRUD
    |--------------------------------------------------------------------------
    */

    public function editDocument(Document $document): Response
    {
        $this->authorize('update', $document);

        $document->load('folder:id,name');

        return Inertia::render('portal/documents/edit', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'extension' => $document->extension,
                'folder' => [
                    'id' => $document->folder->id,
                    'name' => $document->folder->name,
                ],
                'current_version' => $document->currentVersion
                    ? [
                        'original_name'
                            => $document->currentVersion->original_name,
                        'version_number'
                            => $document->currentVersion->version_number,
                        'size' => $document->currentVersion->size,
                    ]
                    : null,
            ],
        ]);
    }

    public function updateDocument(
        UpdatePortalDocumentRequest $request,
        Document $document,
    ): RedirectResponse {
        $before = [
            'title' => $document->title,
            'description' => $document->description,
        ];

        $folderId = $document->folder_id;

        $document->update($request->validated());

        $this->activityLogService->record(
            action: 'document.updated',
            subject: $document,
            description: "Document '{$document->title}' was updated.",
            properties: [
                'document_id' => $document->id,
                'folder_id' => $folderId,
                'before' => $before,
                'after' => [
                    'title' => $document->title,
                    'description' => $document->description,
                ],
            ],
            user: $request->user(),
            request: $request,
        );

        return to_route('portal.folders.show', $folderId)
            ->with('success', 'Document details updated successfully.');
    }

    /**
     * Soft-delete the document, retaining its version files and history.
     */
    public function destroyDocument(
        Request $request,
        Document $document,
    ): RedirectResponse {
        $this->authorize('delete', $document);

        $folderId = $document->folder_id;
        $documentId = $document->id;
        $title = $document->title;

        DB::transaction(function () use (
            $request,
            $document,
            $documentId,
            $folderId,
            $title,
        ): void {
            $document->delete();

            $this->activityLogService->record(
                action: 'document.deleted',
                subject: $document,
                description: "Document '{$title}' was deleted.",
                properties: [
                    'document_id' => $documentId,
                    'folder_id' => $folderId,
                    'title' => $title,
                    'soft_deleted' => true,
                ],
                user: $request->user(),
                request: $request,
            );
        });

        return to_route('portal.folders.show', $folderId)
            ->with('success', 'Document deleted successfully.');
    }
}
