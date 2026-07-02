<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_override_grants_access_to_otherwise_blocked_module(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        UserPermission::create([
            'user_id' => $user->id, 'module' => 'companies',
            'can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false,
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/companies')->assertOk();
    }

    public function test_user_override_blocks_module_even_if_role_matrix_allows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        UserPermission::create([
            'user_id' => $admin->id, 'module' => 'companies',
            'can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false,
        ]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/companies')->assertForbidden();
    }

    public function test_reset_reverts_user_to_role_defaults(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        UserPermission::create([
            'user_id' => $user->id, 'module' => 'companies',
            'can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/v1/users/{$user->id}/permissions")->assertOk();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $user->id]);

        // user can no longer access companies (back to role default: blocked)
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/companies')->assertForbidden();
    }

    public function test_role_change_clears_user_overrides(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        UserPermission::create([
            'user_id' => $user->id, 'module' => 'companies',
            'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/users/{$user->id}", ['name' => $user->name, 'email' => $user->email, 'role' => 'admin', 'level' => $user->level ?? 'staff'])
            ->assertOk();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $user->id]);
    }

    public function test_my_permissions_returns_merged_effective_permissions(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        // Override: grant companies view
        UserPermission::create([
            'user_id' => $user->id, 'module' => 'companies',
            'can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/my-permissions')->assertOk();

        $data = $response->json('data');
        $this->assertTrue($data['companies']['can_view']);
        $this->assertFalse($data['companies']['can_create']);
        // tags: role default allows view+create
        $this->assertTrue($data['tags']['can_view']);
        $this->assertFalse($data['departments']['can_view']);
    }

    public function test_admin_can_update_user_permissions(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $permissions = collect(['companies', 'departments', 'floors', 'rooms', 'cabinets', 'cabinet_slots', 'categories', 'tags', 'users'])
            ->map(fn ($mod) => ['module' => $mod, 'can_view' => $mod === 'companies', 'can_create' => false, 'can_update' => false, 'can_delete' => false])
            ->values()
            ->all();

        Sanctum::actingAs($admin);
        $this->putJson("/api/v1/users/{$user->id}/permissions", ['permissions' => $permissions])->assertOk();

        $this->assertDatabaseHas('user_permissions', ['user_id' => $user->id, 'module' => 'companies', 'can_view' => true]);
    }

    public function test_non_admin_cannot_update_user_permissions(): void
    {
        $user   = User::factory()->create(['role' => 'user']);
        $other  = User::factory()->create(['role' => 'user']);

        $permissions = collect(['companies', 'departments', 'floors', 'rooms', 'cabinets', 'cabinet_slots', 'categories', 'tags', 'users'])
            ->map(fn ($mod) => ['module' => $mod, 'can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false])
            ->values()
            ->all();

        Sanctum::actingAs($other);
        $this->putJson("/api/v1/users/{$user->id}/permissions", ['permissions' => $permissions])->assertForbidden();
    }
}
