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

class NotificationDatabaseChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_otp_stores_an_unread_database_notification_for_pic(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $requester = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/archives/{$archive->id}/request-otp")->assertOk();

        $this->assertSame(1, $pic->fresh()->unreadNotifications()->count());
        $this->assertSame('otp_requested', $pic->fresh()->notifications()->first()->data['type']);
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
        ]);
    }
}
