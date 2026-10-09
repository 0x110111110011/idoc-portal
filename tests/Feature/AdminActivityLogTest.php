<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_activity_history(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'document.uploaded',
            'subject_type' => null,
            'subject_id' => null,
            'description' => 'A document was uploaded.',
            'properties' => ['version_number' => 1],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('admin/activity-logs/index')
                    ->has('logs.data', 1)
                    ->where(
                        'logs.data.0.action',
                        'document.uploaded',
                    )
                    ->where(
                        'logs.data.0.description',
                        'A document was uploaded.',
                    )
            );
    }

    public function test_regular_user_cannot_view_activity_history(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_successful_web_login_and_logout_are_logged(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
        ]);

        event(new Login('web', $user, false));
        event(new Logout('web', $user));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }
}
