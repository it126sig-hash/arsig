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
use App\Notifications\ArchiveExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $department = Department::create(['name' => 'Legal']);
        $pic = User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
        $archive = $this->createArchive($pic);

        $pic->notify(new ArchiveExpiringNotification($archive));
        $pic->notify(new ArchiveExpiringNotification($archive));

        Sanctum::actingAs($pic);

        $response = $this->getJson('/api/v1/notifications')->assertOk();
        $notificationId = $response->json('data.data.0.id');
        $this->assertSame(2, $response->json('data.total'));

        $this->postJson("/api/v1/notifications/{$notificationId}/read")->assertOk();
        $this->assertSame(1, $pic->fresh()->unreadNotifications()->count());

        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->assertSame(0, $pic->fresh()->unreadNotifications()->count());
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
