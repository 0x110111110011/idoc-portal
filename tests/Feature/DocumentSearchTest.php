<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_documents_in_accessible_folders(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $anotherUser = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ownFolder = Folder::query()->create([
            'name' => 'My Folder',
            'description' => null,
            'created_by' => $user->id,
        ]);

        $privateFolder = Folder::query()->create([
            'name' => 'Private Folder',
            'description' => null,
            'created_by' => $anotherUser->id,
        ]);

        $ownDocument = $this->createDocument(
            $ownFolder,
            $user,
            'My Report',
            'pdf',
            'my-report.pdf',
        );

        $this->createDocument(
            $privateFolder,
            $anotherUser,
            'Private Report',
            'pdf',
            'private-report.pdf',
        );

        $this->actingAs($user)
            ->get(route('portal.documents.index'))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('portal/documents/index')
                    ->has('documents.data', 1)
                    ->where('documents.data.0.id', $ownDocument->id)
            );
    }

    public function test_search_folder_filter_and_file_type_work_together(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $folder = Folder::create([
            'name' => 'Reports',
            'description' => null,
            'created_by' => $admin->id,
        ]);

        $matchingDocument = $this->createDocument(
            $folder,
            $admin,
            'Annual Report',
            'pdf',
            'annual-report.pdf',
        );

        $this->createDocument(
            $folder,
            $admin,
            'Meeting Notes',
            'docx',
            'meeting-notes.docx',
        );

        $this->actingAs($admin)
            ->get(route('portal.documents.index', [
                'search' => 'Annual',
                'folder_id' => $folder->id,
                'extension' => 'pdf',
                'sort' => 'title',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('portal/documents/index')
                    ->has('documents.data', 1)
                    ->where(
                        'documents.data.0.id',
                        $matchingDocument->id
                    )
                    ->where('filters.extension', 'pdf')
                    ->where('filters.sort', 'title')
            );
    }

    private function createDocument(
        Folder $folder,
        User $uploader,
        string $title,
        string $extension,
        string $originalName,
    ): Document {
        $document = Document::create([
            'folder_id' => $folder->id,
            'title' => $title,
            'description' => null,
            'extension' => $extension,
            'uploaded_by' => $uploader->id,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'original_name' => $originalName,
            'stored_path' => "testing/{$originalName}",
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/octet-stream',
            },
            'size' => 1024,
            'checksum' => hash('sha256', $originalName),
            'uploaded_by' => $uploader->id,
        ]);

        $document->update([
            'current_version_id' => $version->id,
        ]);

        return $document->fresh();
    }
}
