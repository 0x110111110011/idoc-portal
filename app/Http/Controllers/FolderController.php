<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareFolderRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class FolderController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        //
    }
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $folders = Folder::query()
            ->with('creator:id,name,email')
            ->withCount('documents')
            ->where(function ($query) use ($user) {
                $query
                    ->where('created_by', $user->id)
                    ->orWhereHas('users', function ($query) use ($user) {
                        $query->where('users.id', $user->id);
                    });

                if ($user->role === 'admin') {
                    $query->orWhereRaw('1 = 1');
                }
            })
            ->latest()
            ->paginate(20);

        return response()->json($folders);
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $folder = Folder::query()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
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

        return response()->json([
            'message' => 'Folder created successfully.',
            'data' => $folder->load('creator:id,name,email'),
        ], 201);
    }

    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $folder->load([
            'creator:id,name,email',
            'users:id,name,email',
            'documents.currentVersion',
        ]);

        return response()->json([
            'data' => $folder,
        ]);
    }

    public function update(
        UpdateFolderRequest $request,
        Folder $folder,
    ): JsonResponse {
        $oldValues = $folder->only([
            'name',
            'description',
        ]);

        $folder->update($request->validated());

        $this->activityLogService->record(
            action: 'folder.updated',
            subject: $folder,
            description: "Folder '{$folder->name}' was updated.",
            properties: [
                'before' => $oldValues,
                'after' => $folder->only([
                    'name',
                    'description',
                ]),
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Folder updated successfully.',
            'data' => $folder->fresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Folder $folder,
    ): JsonResponse {
        $this->authorize('delete', $folder);

        $folderName = $folder->name;
        $folderId = $folder->id;

        $folder->delete();

        $this->activityLogService->record(
            action: 'folder.deleted',
            subject: $folder,
            description: "Folder '{$folderName}' was deleted.",
            properties: [
                'folder_id' => $folderId,
                'name' => $folderName,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Folder deleted successfully.',
        ]);
    }

    public function share(
        ShareFolderRequest $request,
        Folder $folder,
    ): JsonResponse {
        $user = User::query()->findOrFail(
            $request->validated('user_id')
        );

        $access = $request->validated('access');

        $folder->users()->syncWithoutDetaching([
            $user->id => [
                'access' => $access,
            ],
        ]);

        $this->activityLogService->record(
            action: 'folder.access_updated',
            subject: $folder,
            description: "Access for '{$user->email}' was set to '{$access}'.",
            properties: [
                'folder_id' => $folder->id,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'access' => $access,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Folder access updated successfully.',
        ]);
    }

    public function removeShare(
        Request $request,
        Folder $folder,
        User $user,
    ): JsonResponse {
        $this->authorize('manageAccess', $folder);

        $folder->users()->detach($user->id);

        $this->activityLogService->record(
            action: 'folder.access_revoked',
            subject: $folder,
            description: "Access for '{$user->email}' was revoked.",
            properties: [
                'folder_id' => $folder->id,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
            ],
            user: $request->user(),
            request: $request,
        );

        return response()->json([
            'message' => 'Folder access removed successfully.',
        ]);
    }
}
