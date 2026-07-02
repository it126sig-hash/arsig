<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\Cabinet;
use App\Models\CabinetSlot;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArchiveLogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_viewing_a_public_archive_creates_a_view_log(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $viewer = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic, ['is_confidential' => false, 'privacy_type' => 'public']);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/archives/{$archive->id}/log-view")->assertOk();

        $this->assertDatabaseHas('archive_download_logs', [
            'archive_id' => $archive->id,
            'user_id' => $viewer->id,
            'action' => 'view',
        ]);
    }

    public function test_user_without_view_access_to_confidential_archive_cannot_log_a_view(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $intruder = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic, ['is_confidential' => true]);

        Sanctum::actingAs($intruder);
        $this->postJson("/api/v1/archives/{$archive->id}/log-view")->assertForbidden();

        $this->assertDatabaseMissing('archive_download_logs', [
            'archive_id' => $archive->id,
            'user_id' => $intruder->id,
        ]);
    }

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
