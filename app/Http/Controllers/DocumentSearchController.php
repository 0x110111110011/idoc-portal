<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentSearchController extends Controller
{
    private const EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
        'csv',
        'png',
        'jpg',
        'jpeg',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'folder_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'extension' => [
                'nullable',
                Rule::in(self::EXTENSIONS),
            ],
            'sort' => [
                'nullable',
                Rule::in([
                    'title',
                    'created_at',
                    'updated_at',
                ]),
            ],
            'direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],
        ]);

        $search = trim($filters['search'] ?? '');
        $folderId = $filters['folder_id'] ?? null;
        $extension = $filters['extension'] ?? '';
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';

        /*
         * Build the list of folders available to this user.
         * Administrators can see every folder.
         * Other users see owned or shared folders only.
         */
        $accessibleFolders = Folder::query()
            ->select(['id', 'name'])
            ->when(
                ! $user->isAdmin(),
                function (Builder $query) use ($user) {
                    $query->where(function (Builder $query) use ($user) {
                        $query
                            ->where('created_by', $user->id)
                            ->orWhereHas(
                                'users',
                                fn(Builder $users)
                                    => $users->where('users.id', $user->id)
                            );
                    });
                }
            )
            ->orderBy('name')
            ->get();

        /*
         * Scope documents through their folder first.
         * Filtering must never expand the user's permissions.
         */
        $documentsQuery = Document::query()
            ->with([
                'folder:id,name',
                'uploader:id,name',
                'currentVersion:id,document_id,version_number,original_name,mime_type,size,created_at',
            ])
            ->whereHas(
                'folder',
                function (Builder $query) use (
                    $user,
                    $folderId
                ) {
                    if (! $user->isAdmin()) {
                        $query->where(function (Builder $query) use ($user) {
                            $query
                                ->where('created_by', $user->id)
                                ->orWhereHas(
                                    'users',
                                    fn(Builder $users)
                                        => $users->where('users.id', $user->id)
                                );
                        });
                    }

                    if ($folderId !== null && $folderId !== '') {
                        $query->where('id', $folderId);
                    }
                }
            );

        /*
         * Search document titles and original uploaded filenames.
         * Query values are bound through Eloquent.
         */
        if ($search !== '') {
            $pattern = "%{$search}%";

            $documentsQuery->where(function (Builder $query) use ($pattern) {
                $query
                    ->where('title', 'ilike', $pattern)
                    ->orWhereHas(
                        'currentVersion',
                        fn(Builder $versionQuery)
                            => $versionQuery->where(
                                'original_name',
                                'ilike',
                                $pattern
                            )
                    );
            });
        }

        // Filter by file extension.
        if ($extension !== '') {
            $documentsQuery->where('extension', $extension);
        }

        /*
         * Sort columns and directions are validated against an allowlist
         * before being passed to orderBy().
         */
        $documents = $documentsQuery
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(function (Document $document) {
                $version = $document->currentVersion;

                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'extension' => $document->extension,
                    'created_at' => $document->created_at?->toISOString(),
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

                    'current_version' => $version
                        ? [
                            'id' => $version->id,
                            'version_number' => $version->version_number,
                            'original_name' => $version->original_name,
                            'mime_type' => $version->mime_type,
                            'size' => $version->size,
                            'created_at' => $version->created_at?->toISOString(),
                        ]
                        : null,
                ];
            });

        return Inertia::render('portal/documents/index', [
            'documents' => $documents,

            'folders' => $accessibleFolders,

            'filters' => [
                'search' => $search,
                'folder_id' => $folderId === null
                    ? ''
                    : (string) $folderId,
                'extension' => $extension,
                'sort' => $sort,
                'direction' => $direction,
            ],

            'extensions' => collect(self::EXTENSIONS)
                ->map(fn(string $value) => [
                    'value' => $value,
                    'label' => strtoupper($value),
                ])
                ->values(),
        ]);
    }
}
