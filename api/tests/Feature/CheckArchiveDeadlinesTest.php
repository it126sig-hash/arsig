<?php

namespace Tests\Feature;

use App\Models\Archive;
use App\Models\ArchiveCheckoutLog;
use App\Models\Cabinet;
use App\Models\CabinetSlot;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ArchiveExpiringNotification;
use App\Notifications\ArchiveOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckArchiveDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_pic_when_reminder_date_is_today(): void
    {
        Notification::fake();

        $pic = $this->createPic();
        $archive = $this->createArchive($pic, [
            'reminder_date' => now()->toDateString(),
            'expire_date' => now()->addDays(7)->toDateString(),
        ]);
        $this->createArchive($pic, [
            'reminder_date' => now()->addDays(3)->toDateString(),
            'expire_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->artisan('archives:check-deadlines')->assertExitCode(0);

        Notification::assertSentToTimes($pic, ArchiveExpiringNotification::class, 1);
        Notification::assertSentTo($pic, ArchiveExpiringNotification::class, function ($notification) use ($archive) {
            return $notification->archive->id === $archive->id;
        });
    }

    public function test_notifies_borrower_and_pic_on_first_day_overdue(): void
    {
        Notification::fake();

        $pic = $this->createPic();
        $borrower = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        ArchiveCheckoutLog::create([
            'archive_id' => $archive->id,
            'actor_user_id' => $borrower->id,
            'action' => 'checkout',
            'borrower_name' => 'Borrower',
            'reason' => 'Review',
            'planned_return_date' => now()->subDay()->toDateString(),
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('archives:check-deadlines')->assertExitCode(0);

        Notification::assertSentTo($borrower, ArchiveOverdueNotification::class);
        Notification::assertSentTo($pic, ArchiveOverdueNotification::class);
    }

    public function test_does_not_notify_for_checkout_not_yet_due(): void
    {
        Notification::fake();

        $pic = $this->createPic();
        $borrower = User::factory()->create(['role' => 'user', 'level' => 'staff']);
        $archive = $this->createArchive($pic);

        ArchiveCheckoutLog::create([
            'archive_id' => $archive->id,
            'actor_user_id' => $borrower->id,
            'action' => 'checkout',
            'borrower_name' => 'Borrower',
            'reason' => 'Review',
            'planned_return_date' => now()->addDays(2)->toDateString(),
            'created_at' => now(),
        ]);

        $this->artisan('archives:check-deadlines')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    private function createPic(): User
    {
        $department = Department::create(['name' => 'Legal']);

        return User::factory()->create(['department_id' => $department->id, 'role' => 'user', 'level' => 'supervisor']);
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
