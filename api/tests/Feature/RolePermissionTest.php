<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_is_blocked_from_companies_module_by_default(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/companies')->assertForbidden();
    }

    public function test_admin_role_can_access_companies_module_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/companies')->assertOk();
    }

    public function test_root_bypasses_the_matrix_even_without_a_matrix_row(): void
    {
        $root = User::factory()->create(['role' => 'root']);

        Sanctum::actingAs($root);
        $this->getJson('/api/v1/companies')->assertOk();
    }

    public function test_user_role_can_view_and_create_tags_but_not_delete(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/tags')->assertOk();
        $this->postJson('/api/v1/tags', ['nama' => 'contoh'])->assertCreated();

        $tag = \App\Models\Tag::first();
        $this->deleteJson("/api/v1/tags/{$tag->id}")->assertForbidden();
    }

    public function test_soft_deleted_company_is_excluded_then_restorable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create(['name' => 'PT Contoh']);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/companies/{$company->id}")->assertOk();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        $list = $this->getJson('/api/v1/companies')->json('data');
        $this->assertEmpty(collect($list)->where('id', $company->id));

        $trashed = $this->getJson('/api/v1/companies/trashed')->json('data');
        $this->assertNotEmpty(collect($trashed)->where('id', $company->id));

        $this->postJson("/api/v1/companies/{$company->id}/restore")->assertOk();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_the_permission_matrix_and_it_takes_effect(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/role-permissions', [
            'permissions' => [
                [
                    'role' => 'user',
                    'module' => 'companies',
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ],
            ],
        ])->assertOk();

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/companies')->assertOk();
        $this->postJson('/api/v1/companies', ['name' => 'PT Baru'])->assertForbidden();
    }

    public function test_non_admin_cannot_update_the_permission_matrix(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);
        $this->putJson('/api/v1/role-permissions', ['permissions' => []])->assertForbidden();
    }
}
