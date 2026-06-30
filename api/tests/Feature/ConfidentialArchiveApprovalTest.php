<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveDownloadRequest;
use App\Models\Cabinet;
use App\Models\CabinetSlot;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfidentialArchiveApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_archive_otp_still_finishes_after_pic_approval(): void
    {
        Notification::fake();

        [$pic, $requester] = $this->createUsers();
        $archive = $this->createArchive($pic, ['is_confidential' => false]);

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/archives/{$archive->id}/request-otp")
            ->assertOk();

        $downloadRequest = ArchiveDownloadRequest::query()->firstOrFail();

        Sanctum::actingAs($pic);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_stage', 'completed');

        $this->assertDatabaseHas('archive_download_requests', [
            'id' => $downloadRequest->id,
            'status' => 'approved',
            'approval_stage' => 'completed',
            'requires_department_approval' => false,
            'is_verified' => false,
        ]);

        $this->assertNotNull($downloadRequest->fresh()->otp_code);
    }

    public function test_confidential_archive_requires_pic_then_department_head_before_otp(): void
    {
        Notification::fake();

        [$pic, $requester, $departmentHead] = $this->createUsers(withDepartmentHead: true);
        $archive = $this->createArchive($pic, ['is_confidential' => true]);

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/archives/{$archive->id}/request-otp")
            ->assertOk();

        $downloadRequest = ArchiveDownloadRequest::query()->firstOrFail();

        Sanctum::actingAs($pic);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.approval_stage', 'department')
            ->assertJsonPath('data.otp_code', null);

        $this->assertDatabaseHas('archive_download_requests', [
            'id' => $downloadRequest->id,
            'status' => 'pending',
            'approval_stage' => 'department',
            'requires_department_approval' => true,
            'pic_approved_by_user_id' => $pic->id,
        ]);

        Sanctum::actingAs($departmentHead);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_stage', 'completed');

        $fresh = $downloadRequest->fresh();
        $this->assertNotNull($fresh->otp_code);
        $this->assertSame($departmentHead->id, $fresh->department_approved_by_user_id);
    }

    public function test_any_of_multiple_department_heads_can_approve_and_all_are_notified(): void
    {
        Notification::fake();

        $department = Department::create(['name' => 'Finance']);
        $headOne = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'manager']);
        $headTwo = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'manager']);
        $department->heads()->attach([$headOne->id, $headTwo->id]);

        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $requester = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic, ['is_confidential' => true]);

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/archives/{$archive->id}/request-otp")->assertOk();

        $downloadRequest = ArchiveDownloadRequest::query()->firstOrFail();

        Sanctum::actingAs($pic);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")->assertOk();

        Notification::assertSentTo([$headOne, $headTwo], \App\Notifications\DepartmentApprovalRequestedNotification::class);

        Sanctum::actingAs($headTwo);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_stage', 'completed');

        $this->assertSame($headTwo->id, $downloadRequest->fresh()->department_approved_by_user_id);
    }

    public function test_wrong_user_cannot_approve_confidential_request_stages(): void
    {
        Notification::fake();

        [$pic, $requester] = $this->createUsers(withDepartmentHead: true);
        $archive = $this->createArchive($pic, ['is_confidential' => true]);
        $intruder = User::factory()->create(['role' => 'user', 'level' => 'staff']);

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/archives/{$archive->id}/request-otp")
            ->assertOk();

        $downloadRequest = ArchiveDownloadRequest::query()->firstOrFail();

        Sanctum::actingAs($intruder);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertForbidden();

        Sanctum::actingAs($pic);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertOk();

        Sanctum::actingAs($intruder);
        $this->postJson("/api/v1/archive-requests/{$downloadRequest->id}/approve")
            ->assertForbidden();
    }

    public function test_confidential_location_is_masked_and_endpoint_restricted_for_unrelated_user(): void
    {
        [$pic, $requester, $departmentHead] = $this->createUsers(withDepartmentHead: true);
        $archive = $this->createArchive($pic, [
            'is_confidential' => true,
            'privacy_type' => 'public',
        ]);

        Sanctum::actingAs($requester);
        $this->getJson('/api/v1/archives')
            ->assertOk()
            ->assertJsonPath('data.0.id', $archive->id)
            ->assertJsonPath('data.0.floor_id', null)
            ->assertJsonPath('data.0.room_id', null)
            ->assertJsonPath('data.0.cabinet_id', null)
            ->assertJsonPath('data.0.cabinet_slot_id', null)
            ->assertJsonPath('data.0.can_view_location', false)
            ->assertJsonPath('data.0.can_move_location', false);

        $this->getJson("/api/v1/archives/{$archive->id}/location-histories")
            ->assertForbidden();

        Sanctum::actingAs($departmentHead);
        $this->getJson('/api/v1/archives')
            ->assertOk()
            ->assertJsonPath('data.0.floor_id', $archive->floor_id)
            ->assertJsonPath('data.0.can_view_location', true);
    }

    public function test_public_confidential_archive_still_requires_verified_otp_for_regular_view_access(): void
    {
        Notification::fake();

        [$pic, $requester, $departmentHead] = $this->createUsers(withDepartmentHead: true);
        $archive = $this->createArchive($pic, [
            'is_confidential' => true,
            'privacy_type' => 'public',
        ]);

        $this->assertFalse($requester->can('view', $archive));
        $this->assertTrue($pic->can('view', $archive));
        $this->assertTrue($departmentHead->can('view', $archive));

        $downloadRequest = ArchiveDownloadRequest::create([
            'archive_id' => $archive->id,
            'requester_user_id' => $requester->id,
            'status' => 'approved',
            'requires_department_approval' => true,
            'approval_stage' => 'completed',
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(15),
            'is_verified' => true,
        ]);

        $this->assertTrue($requester->can('view', $archive));

        $downloadRequest->update(['otp_expires_at' => now()->subMinute()]);

        $this->assertFalse($requester->can('view', $archive));
    }

    /**
     * @return array{0: User, 1: User, 2?: User}
     */
    private function createUsers(bool $withDepartmentHead = false): array
    {
        $department = Department::create(['name' => 'Legal']);
        $departmentHead = null;

        if ($withDepartmentHead) {
            $departmentHead = User::factory()->create([
                'department_id' => $department->id,
                'role' => 'user',
                'level' => 'manager',
            ]);
            $department->heads()->attach($departmentHead->id);
        }

        $pic = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'user',
            'level' => 'supervisor',
        ]);

        $requester = User::factory()->create([
            'role' => 'user',
            'level' => 'staff',
        ]);

        return array_values(array_filter([$pic, $requester, $departmentHead]));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createArchive(User $pic, array $overrides = []): Archive
    {
        $company = Company::create(['name' => 'Acme']);
        $category = Category::create(['company_id' => $company->id, 'name' => 'Contracts']);
        $floor = Floor::create(['name' => 'Floor 1']);
        $room = Room::create(['floor_id' => $floor->id, 'name' => 'Room 1', 'points' => [[0, 0], [1, 1]]]);
        $cabinet = Cabinet::create(['room_id' => $room->id, 'name' => 'Cabinet 1', 'points' => [[0, 0], [1, 1]]]);
        $slot = CabinetSlot::create(['cabinet_id' => $cabinet->id, 'name' => 'A1']);

        return Archive::create(array_merge([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => 'Important Agreement',
            'file_number' => 'AGR-001',
            'archive_type' => 'physical_only',
            'privacy_type' => 'private',
            'download_policy' => 'request_to_pic',
            'status' => 'active',
            'pic_user_id' => $pic->id,
            'issue_date' => now()->toDateString(),
            'floor_id' => $floor->id,
            'room_id' => $room->id,
            'cabinet_id' => $cabinet->id,
            'cabinet_slot_id' => $slot->id,
            'created_by' => $pic->id,
            'is_confidential' => false,
        ], $overrides));
    }
}
