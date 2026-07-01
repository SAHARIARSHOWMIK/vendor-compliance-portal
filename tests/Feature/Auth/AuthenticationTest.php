<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Login / logout basics
    // -----------------------------------------------------------------

    public function test_login_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Vendor Compliance Portal');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::ComplianceAdmin,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::ComplianceAdmin,
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    // -----------------------------------------------------------------
    // Role-based post-login redirection
    // -----------------------------------------------------------------

    public function test_super_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::SuperAdmin,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_compliance_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::ComplianceAdmin,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_reviewer_is_redirected_to_review_queue(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::Reviewer,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('reviewer.queue'));
    }

    public function test_vendor_user_is_redirected_to_vendor_portal(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::VendorUser,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('vendor-portal.dashboard'));
    }

    public function test_auditor_is_redirected_to_auditor_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => RoleName::Auditor,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('auditor.dashboard'));
    }

    // -----------------------------------------------------------------
    // Role-based access control: admin routes
    // -----------------------------------------------------------------

    public function test_compliance_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_reviewer_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::Reviewer]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_vendor_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::VendorUser]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_auditor_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::Auditor]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Role-based access control: vendor portal routes
    // -----------------------------------------------------------------

    public function test_vendor_user_can_access_vendor_portal(): void
    {
        $user = User::factory()->create(['role' => RoleName::VendorUser]);

        // No vendor linked yet - middleware lets them through to the
        // controller, which returns 200 with the "not linked" message.
        $this->actingAs($user)->get(route('vendor-portal.dashboard'))
            ->assertStatus(200);
    }

    public function test_compliance_admin_cannot_access_vendor_portal_route(): void
    {
        // The vendor-portal route group is 'role:vendor_user' only.
        // Internal staff access vendor data via the admin routes.
        $user = User::factory()->create(['role' => RoleName::ComplianceAdmin]);

        $this->actingAs($user)->get(route('vendor-portal.dashboard'))
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Role-based access control: auditor routes
    // -----------------------------------------------------------------

    public function test_auditor_can_access_auditor_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::Auditor]);

        $this->actingAs($user)->get(route('auditor.dashboard'))
            ->assertStatus(200);
    }

    public function test_vendor_user_cannot_access_auditor_dashboard(): void
    {
        $user = User::factory()->create(['role' => RoleName::VendorUser]);

        $this->actingAs($user)->get(route('auditor.dashboard'))
            ->assertStatus(403);
    }
}
