<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_real_database_counts(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $regularUser = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $folder = Folder::create([
            'name' => 'Company Reports',
            'description' => null,
            'created_by' => $admin->id,
        ]);

        Document::create([
            'folder_id' => $folder->id,
            'title' => 'Annual Report',
            'description' => null,
            'extension' => 'pdf',
            'uploaded_by' => $admin->id,
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'folder.created',
            'description' => 'Folder created.',
            'properties' => null,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('admin/dashboard')
                    ->where('stats.totalUsers', 2)
                    ->where('stats.activeUsers', 2)
                    ->where('stats.inactiveUsers', 0)
                    ->where('stats.totalFolders', 1)
                    ->where('stats.totalDocuments', 1)
                    ->where('stats.totalActivities', 1)
                    ->has('recentDocuments', 1)
                    ->has('recentActivities', 1)
            );
    }

    public function test_user_dashboard_only_counts_accessible_documents(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ownedFolder = Folder::create([
            'name' => 'My Documents',
            'description' => null,
            'created_by' => $user->id,
        ]);

        $sharedFolder = Folder::create([
            'name' => 'Shared Documents',
            'description' => null,
            'created_by' => $otherUser->id,
        ]);

        $privateFolder = Folder::create([
            'name' => 'Private Documents',
            'description' => null,
            'created_by' => $otherUser->id,
        ]);

        $sharedFolder->users()->attach($user->id, [
            'access' => 'view',
        ]);

        Document::create([
            'folder_id' => $ownedFolder->id,
            'title' => 'Owned Report',
            'description' => null,
            'extension' => 'pdf',
            'uploaded_by' => $user->id,
        ]);

        Document::create([
            'folder_id' => $sharedFolder->id,
            'title' => 'Shared Report',
            'description' => null,
            'extension' => 'docx',
            'uploaded_by' => $otherUser->id,
        ]);

        Document::create([
            'folder_id' => $privateFolder->id,
            'title' => 'Private Report',
            'description' => null,
            'extension' => 'pdf',
            'uploaded_by' => $otherUser->id,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'document.downloaded',
            'description' => 'Downloaded a document.',
            'properties' => null,
            'ip_address' => null,
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'document.uploaded',
            'description' => 'Uploaded a private document.',
            'properties' => null,
            'ip_address' => null,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('dashboard')
                    ->where('stats.accessibleFolders', 2)
                    ->where('stats.accessibleDocuments', 2)
                    ->has('folders', 2)
                    ->has('recentDocuments', 2)
                    ->has('recentActivities', 1)
                    ->where(
                        'recentActivities.0.action',
                        'document.downloaded',
                    )
            );
    }
}
