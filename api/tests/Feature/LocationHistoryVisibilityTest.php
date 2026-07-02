<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveLocationLog;
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

class LocationHistoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_location_history_of_archives_they_are_pic_of(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $bystander = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        ArchiveLocationLog::create(['archive_id' => $archive->id, 'user_id' => $pic->id]);

        Sanctum::actingAs($bystander);
        $this->getJson('/api/v1/location-histories')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');

        Sanctum::actingAs($pic);
        $this->getJson('/api/v1/location-histories')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_department_head_sees_own_pic_archives_plus_archives_uploaded_by_their_department(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $otherDepartment = Department::create(['name' => 'Legal']);
        $departmentHead = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'manager']);
        $department->heads()->attach($departmentHead->id);

        $deptMember = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'staff']);
        $outsidePic = User::factory()->create(['department_id' => $otherDepartment->id, 'role' => 'user', 'level' => 'supervisor']);

        $uploadedByDept = $this->createArchive($outsidePic, ['created_by' => $deptMember->id]);
        $ownPic = $this->createArchive($departmentHead, ['created_by' => $outsidePic->id]);
        $unrelated = $this->createArchive($outsidePic, ['created_by' => $outsidePic->id]);

        ArchiveLocationLog::create(['archive_id' => $uploadedByDept->id, 'user_id' => $outsidePic->id]);
        ArchiveLocationLog::create(['archive_id' => $ownPic->id, 'user_id' => $outsidePic->id]);
        ArchiveLocationLog::create(['archive_id' => $unrelated->id, 'user_id' => $outsidePic->id]);

        Sanctum::actingAs($departmentHead);
        $response = $this->getJson('/api/v1/location-histories')->assertOk();
        $response->assertJsonCount(2, 'data.data');

        $archiveIds = collect($response->json('data.data'))->pluck('archive_id')->sort()->values();
        $this->assertEquals(collect([$uploadedByDept->id, $ownPic->id])->sort()->values(), $archiveIds);
    }

    public function test_admin_sees_all_location_history(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $admin = User::factory()->create(['role' => 'admin', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        ArchiveLocationLog::create(['archive_id' => $archive->id, 'user_id' => $pic->id]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/location-histories')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
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
