<?php

namespace Tests\Feature\Notification;

use App\Enums\RoleName;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_only_their_notifications(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $other = User::factory()->create(['role' => RoleName::Reviewer]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Your compliance alert',
            'message' => 'A vendor document requires attention.',
            'type' => 'action_required',
        ]);
        Notification::create([
            'user_id' => $other->id,
            'title' => 'Private reviewer alert',
            'message' => 'This should not be visible to the admin.',
            'type' => 'info',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Your compliance alert')
            ->assertDontSee('Private reviewer alert');
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Review ready',
            'message' => 'Evidence is ready for review.',
            'type' => 'action_required',
        ]);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $other = User::factory()->create(['role' => RoleName::Reviewer]);
        $notification = Notification::create([
            'user_id' => $other->id,
            'title' => 'Private alert',
            'message' => 'Restricted notification.',
            'type' => 'info',
        ]);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();
    }

    public function test_mark_all_read_only_updates_current_user_notifications(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $other = User::factory()->create(['role' => RoleName::Reviewer]);

        Notification::create(['user_id' => $user->id, 'title' => 'A', 'message' => 'A', 'type' => 'info']);
        Notification::create(['user_id' => $user->id, 'title' => 'B', 'message' => 'B', 'type' => 'warning']);
        $otherNotification = Notification::create(['user_id' => $other->id, 'title' => 'C', 'message' => 'C', 'type' => 'info']);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
        $this->assertFalse($otherNotification->fresh()->is_read);
    }
}
