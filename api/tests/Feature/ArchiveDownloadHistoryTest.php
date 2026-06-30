<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveDownloadLog;
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

class ArchiveDownloadHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_confidential_download_is_visible_to_any_authenticated_user(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $downloader = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $bystander = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic, ['is_confidential' => false]);

        ArchiveDownloadLog::create(['archive_id' => $archive->id, 'user_id' => $downloader->id, 'created_at' => now()]);

        Sanctum::actingAs($bystander);
        $this->getJson('/api/v1/archive-downloads/history')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_confidential_download_is_only_visible_to_pic_and_department_head(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $departmentHead = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'manager']);
        $department->heads()->attach($departmentHead->id);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $downloader = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $outsider = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic, ['is_confidential' => true]);

        ArchiveDownloadLog::create(['archive_id' => $archive->id, 'user_id' => $downloader->id, 'created_at' => now()]);

        Sanctum::actingAs($outsider);
        $this->getJson('/api/v1/archive-downloads/history')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($pic);
        $this->getJson('/api/v1/archive-downloads/history')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Sanctum::actingAs($departmentHead);
        $this->getJson('/api/v1/archive-downloads/history')
            ->assertOk()
            ->assertJsonCount(1, 'data');
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
