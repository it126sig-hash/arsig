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
use App\Notifications\ArchiveDownloadedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArchiveDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_by_other_user_logs_event_and_notifies_pic(): void
    {
        Notification::fake();
        Storage::fake('local');

        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $downloader = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        Storage::disk('local')->put($archive->file_path, 'dummy content');

        Sanctum::actingAs($downloader);
        $this->get("/api/v1/archives/{$archive->id}/download")->assertOk();

        $this->assertDatabaseHas('archive_download_logs', [
            'archive_id' => $archive->id,
            'user_id' => $downloader->id,
        ]);

        Notification::assertSentTo($pic, ArchiveDownloadedNotification::class);
    }

    public function test_pic_downloading_own_archive_does_not_self_notify(): void
    {
        Notification::fake();
        Storage::fake('local');

        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $archive = $this->createArchive($pic);

        Storage::disk('local')->put($archive->file_path, 'dummy content');

        Sanctum::actingAs($pic);
        $this->get("/api/v1/archives/{$archive->id}/download")->assertOk();

        $this->assertDatabaseHas('archive_download_logs', [
            'archive_id' => $archive->id,
            'user_id' => $pic->id,
        ]);

        Notification::assertNothingSent();
    }

    private function createArchive(User $pic): Archive
    {
        $company = Company::create(['name' => 'Acme']);
        $category = Category::create(['company_id' => $company->id, 'name' => 'Contracts']);
        $floor = Floor::create(['name' => 'Floor 1']);
        $room = Room::create(['floor_id' => $floor->id, 'name' => 'Room 1', 'points' => [[0, 0], [1, 1]]]);
        $cabinet = Cabinet::create(['room_id' => $room->id, 'name' => 'Cabinet 1', 'points' => [[0, 0], [1, 1]]]);
        $slot = CabinetSlot::create(['cabinet_id' => $cabinet->id, 'name' => 'A1']);

        return Archive::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => 'Important Agreement',
            'file_number' => 'AGR-001',
            'archive_type' => 'digital_only',
            'file_path' => 'archives/agr-001.pdf',
            'privacy_type' => 'public',
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
        ]);
    }
}
