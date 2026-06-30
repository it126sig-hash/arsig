# Confidential Approval Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add organizational user levels, department-head settings, confidential archive marking, two-layer OTP approval for confidential files, and stricter physical-location access.

**Architecture:** Keep `role` as application permission (`root`, `admin`, `user`) and add `level` as organizational seniority (`staff`, `supervisor`, `manager`, `direksi`). Store department head directly on `departments.head_user_id` because the current Department CRUD already functions as settings. Store confidential enforcement as `archives.is_confidential`; render it as a Confidential tag in UI and optionally sync with the existing tag list for display/search.

**Tech Stack:** Laravel API in `api/`, Sanctum auth, Eloquent models/services/controllers, Vue 3 + Pinia + PrimeVue frontend in `frontend/`.

---

## Context From Repo And Graphify

Graphify reports the relevant communities as `Archive API Layer`, `Admin CRUD APIs`, `Archive Checkout Flow`, and a hyperedge named `Archive Access & Management Workflow` connecting `archive_management`, `otp_access_control`, `checkout_tracking`, and `physical_location_system`.

Current backend facts:

- `api/app/Models/User.php` has `department_id` and `role`, but no organizational `level`.
- `api/app/Models/Department.php` only stores `name`, so department head is not configurable yet.
- `api/app/Models/Archive.php` has `privacy_type`, `download_policy`, `pic_user_id`, physical location fields, and tags, but no durable confidential flag.
- `api/app/Models/ArchiveDownloadRequest.php` has one `status`, one `reviewed_by_user_id`, one `otp_code`, and one `is_verified`.
- `api/app/Services/ArchiveRequestService.php::approve()` immediately generates OTP after PIC/admin approval.
- `api/app/Http/Controllers/ArchiveController.php::moveLocation()` uses `ArchivePolicy::update`, so current physical-location mutation follows PIC/admin access only.

Design decision: use `archives.is_confidential` as the security source of truth. A normal user-editable tag named Confidential can be displayed for UX, but access control must not rely only on a mutable tag name.

---

## Target Behavior

1. User management can assign each user one `level`: `staff`, `supervisor`, `manager`, or `direksi`.
2. Department settings can assign a `head_user_id`.
3. Archive upload/edit can mark an archive as confidential.
4. Confidential archives show a Confidential badge/tag in archive list, detail, upload/edit forms, and request approval list.
5. Non-confidential OTP flow remains one layer: requester -> PIC approval -> OTP.
6. Confidential OTP flow becomes two layers: requester -> PIC approval -> Department Head approval -> OTP.
7. For confidential physical archives, physical-location data and move-location action are available only to archive PIC, configured department head, and admin/root.
8. For confidential digital download/preview, requester must pass the two-layer OTP before `ArchivePolicy::view()` grants OTP-based access.

---

## Data Model Plan

### Task 1: Add User Level

**Files:**
- Create: `api/database/migrations/YYYY_MM_DD_HHMMSS_add_level_to_users_table.php`
- Modify: `api/app/Models/User.php`
- Modify: `api/app/Http/Requests/StoreUserRequest.php`
- Modify: `api/app/Http/Requests/UpdateUserRequest.php`
- Modify: `api/app/Services/UserService.php`
- Modify: `frontend/src/views/UserView.vue`

- [ ] **Step 1: Write migration**

Add nullable/default `level` to keep existing users valid:

```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('level', ['staff', 'supervisor', 'manager', 'direksi'])
        ->default('staff')
        ->after('role');
});
```

- [ ] **Step 2: Expose level on User model**

Update `#[Fillable(...)]`:

```php
#[Fillable(['name', 'email', 'password', 'department_id', 'role', 'level'])]
```

- [ ] **Step 3: Validate level on create/update**

Add to both user request classes:

```php
'level' => ['required', Rule::in(['staff', 'supervisor', 'manager', 'direksi'])],
```

- [ ] **Step 4: Add level UI**

In `frontend/src/views/UserView.vue`, add a `levelOptions` select beside Role, include `level` in `form`, `openEdit()`, `resetForm()`, payload, table/mobile display, and error handling.

- [ ] **Step 5: Verify**

Run:

```powershell
cd api
php artisan test
php -l app/Models/User.php
php -l app/Http/Requests/StoreUserRequest.php
php -l app/Http/Requests/UpdateUserRequest.php
cd ..\frontend
npm run build
```

Expected: tests pass or only pre-existing unrelated failures are reported; frontend build completes.

### Task 2: Add Department Head Setting

**Files:**
- Create: `api/database/migrations/YYYY_MM_DD_HHMMSS_add_head_user_id_to_departments_table.php`
- Modify: `api/app/Models/Department.php`
- Modify: `api/app/Http/Requests/StoreDepartmentRequest.php`
- Modify: `api/app/Http/Requests/UpdateDepartmentRequest.php`
- Modify: `api/app/Services/DepartmentService.php`
- Modify: `frontend/src/views/DepartmentView.vue`

- [ ] **Step 1: Write migration**

```php
Schema::table('departments', function (Blueprint $table) {
    $table->foreignId('head_user_id')
        ->nullable()
        ->after('name')
        ->constrained('users')
        ->nullOnDelete();
});
```

- [ ] **Step 2: Add Department relation**

```php
protected $fillable = ['name', 'head_user_id'];

public function head(): BelongsTo
{
    return $this->belongsTo(User::class, 'head_user_id');
}
```

- [ ] **Step 3: Load head in service**

Change `DepartmentService::getAll()`:

```php
return Department::with('head')->withCount('users')->orderBy('name')->get();
```

- [ ] **Step 4: Validate head setting**

Add to store/update request:

```php
'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
```

- [ ] **Step 5: Add department UI field**

In `DepartmentView.vue`, load users through `fetchUsers()`, add a `Select` for `head_user_id`, display the head name in table/mobile list, and submit `head_user_id` with department payload.

- [ ] **Step 6: Verify**

Run backend lint/test and frontend build as in Task 1.

### Task 3: Add Confidential Archive Flag

**Files:**
- Create: `api/database/migrations/YYYY_MM_DD_HHMMSS_add_is_confidential_to_archives_table.php`
- Modify: `api/app/Models/Archive.php`
- Modify: `api/app/Http/Requests/StoreArchiveRequest.php`
- Modify: `api/app/Http/Requests/UpdateArchiveRequest.php`
- Modify: `api/app/Services/ArchiveService.php`
- Modify: `frontend/src/components/ArchiveUploadDialog.vue`
- Modify: `frontend/src/components/ArchiveEditDialog.vue`
- Modify: `frontend/src/components/ArchiveDetailModal.vue`
- Modify: `frontend/src/views/HomeView.vue`

- [ ] **Step 1: Write migration**

```php
Schema::table('archives', function (Blueprint $table) {
    $table->boolean('is_confidential')->default(false)->after('download_policy');
});
```

- [ ] **Step 2: Update model**

Add `is_confidential` to `$fillable` and `$casts`:

```php
'is_confidential',
```

```php
'is_confidential' => 'boolean',
```

- [ ] **Step 3: Validate archive requests**

Add to store/update rules:

```php
'is_confidential' => 'sometimes|boolean',
```

- [ ] **Step 4: Normalize service input**

Before create/update in `ArchiveService`, normalize missing checkbox values:

```php
$data['is_confidential'] = (bool) ($data['is_confidential'] ?? false);
```

- [ ] **Step 5: Add UI checkbox/badge**

Add a PrimeVue checkbox/toggle in upload and edit dialogs. Submit `is_confidential` as `1` or `0`. Show a red/amber Confidential badge in archive cards/detail rows when `archive.is_confidential` is true.

- [ ] **Step 6: Verify**

Create one normal archive and one confidential archive locally. Confirm API returns `is_confidential` and the frontend shows the badge.

---

## Two-Layer OTP Approval Plan

### Task 4: Extend Archive Download Request Workflow

**Files:**
- Create: `api/database/migrations/YYYY_MM_DD_HHMMSS_add_two_layer_approval_to_archive_download_requests_table.php`
- Modify: `api/app/Models/ArchiveDownloadRequest.php`
- Modify: `api/app/Services/ArchiveRequestService.php`
- Modify: `api/app/Http/Controllers/ArchiveRequestController.php`
- Modify: `api/app/Notifications/OtpRequestedNotification.php`
- Create: `api/app/Notifications/DepartmentApprovalRequestedNotification.php`

- [ ] **Step 1: Add approval columns**

```php
Schema::table('archive_download_requests', function (Blueprint $table) {
    $table->boolean('requires_department_approval')->default(false)->after('status');
    $table->enum('approval_stage', ['pic', 'department', 'completed'])->default('pic')->after('requires_department_approval');
    $table->foreignId('pic_approved_by_user_id')->nullable()->after('reviewed_by_user_id')->constrained('users')->nullOnDelete();
    $table->timestamp('pic_approved_at')->nullable()->after('pic_approved_by_user_id');
    $table->foreignId('department_approved_by_user_id')->nullable()->after('pic_approved_at')->constrained('users')->nullOnDelete();
    $table->timestamp('department_approved_at')->nullable()->after('department_approved_by_user_id');
    $table->foreignId('rejected_by_user_id')->nullable()->after('department_approved_at')->constrained('users')->nullOnDelete();
    $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
});
```

- [ ] **Step 2: Set request metadata on create**

In `ArchiveController::requestOtp()`, create requests with:

```php
'requires_department_approval' => (bool) $archive->is_confidential,
'approval_stage' => 'pic',
```

- [ ] **Step 3: Split approval logic**

Refactor `ArchiveRequestService::approve()`:

```php
if ($request->requires_department_approval && $request->approval_stage === 'pic') {
    $request->update([
        'approval_stage' => 'department',
        'pic_approved_by_user_id' => $reviewer->id,
        'pic_approved_at' => now(),
    ]);

    $departmentHead = $request->archive->pic?->department?->head;
    if ($departmentHead) {
        $departmentHead->notify(new DepartmentApprovalRequestedNotification($request->archive, $request->requester));
    }

    return $request->load(['archive', 'requester', 'reviewer']);
}

$otp = (string) random_int(100000, 999999);
$request->update([
    'status' => 'approved',
    'approval_stage' => 'completed',
    'otp_code' => $otp,
    'otp_expires_at' => now()->addMinutes(15),
    'reviewed_by_user_id' => $reviewer->id,
    'department_approved_by_user_id' => $request->requires_department_approval ? $reviewer->id : null,
    'department_approved_at' => $request->requires_department_approval ? now() : null,
    'is_verified' => false,
]);
```

- [ ] **Step 4: Add reviewer authorization helper**

Add service/helper methods:

```php
public function canActOn(User $user, ArchiveDownloadRequest $request): bool
{
    if (in_array($user->role, ['root', 'admin'], true)) {
        return true;
    }

    if ($request->approval_stage === 'pic') {
        return (int) $request->archive->pic_user_id === (int) $user->id;
    }

    if ($request->approval_stage === 'department') {
        return (int) optional($request->archive->pic?->department)->head_user_id === (int) $user->id;
    }

    return false;
}
```

Use it in `ArchiveRequestController::approve()` and `reject()`.

- [ ] **Step 5: Update listForPic to list actionable requests**

For non-admin users, show requests where they are archive PIC or the configured head of the PIC's department:

```php
$query->where(function ($query) use ($user) {
    $query->whereHas('archive', fn ($q) => $q->where('pic_user_id', $user->id))
        ->orWhereHas('archive.pic.department', fn ($q) => $q->where('head_user_id', $user->id));
});
```

- [ ] **Step 6: Handle missing department head**

If a confidential request reaches PIC approval but the PIC has no department head configured, return `422` with:

```text
Kepala departemen belum diatur untuk departemen PIC. Atur kepala departemen terlebih dahulu.
```

This prevents hidden stuck requests.

- [ ] **Step 7: Verify**

Test these flows:

- Normal archive: requester requests OTP; PIC approves; OTP is generated.
- Confidential archive: requester requests OTP; PIC approves; status stays `pending`, `approval_stage` becomes `department`, no OTP yet.
- Confidential archive: department head approves; status becomes `approved`, `approval_stage` becomes `completed`, OTP is generated.
- Wrong user cannot approve either stage.

### Task 5: Update Approval UI

**Files:**
- Modify: `frontend/src/views/RequestApprovalView.vue`
- Modify: `frontend/src/api/archiveRequestApi.js` only if endpoint names change. Prefer no endpoint changes.

- [ ] **Step 1: Add stage display**

Show `approval_stage` and `requires_department_approval` in table:

- `PIC Approval` for stage `pic`.
- `Kepala Departemen` for stage `department`.
- `Selesai` for stage `completed`.

- [ ] **Step 2: Update confirmation text**

For confidential stage `pic`, confirmation must say approval will be forwarded to department head and OTP will not be generated yet.

For confidential stage `department`, confirmation must say OTP will be generated after approval.

- [ ] **Step 3: Hide OTP until final approval**

Only render OTP when:

```js
data.status === 'approved' && data.approval_stage === 'completed' && data.otp_code
```

- [ ] **Step 4: Verify frontend**

Run:

```powershell
cd frontend
npm run build
```

Expected: build succeeds and approval list clearly distinguishes both stages.

---

## Confidential Location Access Plan

### Task 6: Add Policy Methods For Confidential Location

**Files:**
- Modify: `api/app/Policies/ArchivePolicy.php`
- Modify: `api/app/Http/Controllers/ArchiveController.php`
- Modify: `api/app/Services/ArchiveService.php`
- Modify: `frontend/src/views/HomeView.vue`
- Modify: `frontend/src/components/ArchiveDetailModal.vue`
- Modify: `frontend/src/components/MoveLocationDialog.vue`

- [ ] **Step 1: Add helper method to policy**

```php
private function isDepartmentHeadForArchive(User $user, Archive $archive): bool
{
    return (int) optional($archive->pic?->department)->head_user_id === (int) $user->id;
}
```

- [ ] **Step 2: Add location permission**

```php
public function viewLocation(User $user, Archive $archive): bool
{
    if (! $archive->is_confidential) {
        return $this->view($user, $archive);
    }

    return in_array($user->role, ['root', 'admin'], true)
        || (int) $archive->pic_user_id === (int) $user->id
        || $this->isDepartmentHeadForArchive($user, $archive);
}

public function moveLocation(User $user, Archive $archive): bool
{
    return $this->viewLocation($user, $archive);
}
```

- [ ] **Step 3: Use policy in controller**

Change:

```php
$this->authorize('update', $archive);
```

to:

```php
$this->authorize('moveLocation', $archive);
```

in `ArchiveController::moveLocation()`.

For `locationHistories()`, authorize `viewLocation`.

- [ ] **Step 4: Avoid leaking location in archive list**

Add a transformer/resource or service mapping in `ArchiveService::list()` that nulls `floor`, `room`, `cabinet`, `cabinetSlot`, and location IDs for confidential archives when the current user cannot `viewLocation`.

Preferred minimal approach in service after `get()`:

```php
$user = Auth::user();
return $archives->map(function (Archive $archive) use ($user) {
    if ($user && $archive->is_confidential && ! $user->can('viewLocation', $archive)) {
        $archive->setRelation('floor', null);
        $archive->setRelation('room', null);
        $archive->setRelation('cabinet', null);
        $archive->setRelation('cabinetSlot', null);
        $archive->floor_id = null;
        $archive->room_id = null;
        $archive->cabinet_id = null;
        $archive->cabinet_slot_id = null;
    }

    return $archive;
});
```

- [ ] **Step 5: Update frontend conditional actions**

Use a backend-provided `can_view_location`/`can_move_location` field if added. If not added yet, infer cautiously only for UI hiding:

```js
const canSeeLocation = archive.can_view_location !== false
```

Backend must remain authoritative; frontend hiding is only UX.

- [ ] **Step 6: Verify**

Test as requester, unrelated user, PIC, department head, and admin:

- Unrelated user cannot see confidential physical location in archive list/detail.
- Unrelated user gets 403 on move-location and location-history endpoints.
- PIC and department head can see/move confidential physical location.

---

## Test Plan

### Task 7: Add Backend Feature Tests

**Files:**
- Create: `api/tests/Feature/ConfidentialArchiveApprovalTest.php`
- Create or update factories if missing: `api/database/factories/UserFactory.php`
- Update test helpers only if needed.

- [ ] **Step 1: Test normal OTP stays unchanged**

Create non-confidential archive with PIC. Request OTP as another user. Approve as PIC. Assert `status=approved`, `approval_stage=completed`, `otp_code` is not null.

- [ ] **Step 2: Test confidential first approval**

Create department with `head_user_id`, PIC in that department, confidential archive. Request OTP. Approve as PIC. Assert `status=pending`, `approval_stage=department`, `otp_code=null`.

- [ ] **Step 3: Test confidential second approval**

Continue from stage `department`. Approve as department head. Assert `status=approved`, `approval_stage=completed`, `otp_code` exists.

- [ ] **Step 4: Test unauthorized stage approval**

Assert unrelated user receives 403 on both approval stages.

- [ ] **Step 5: Test confidential location protection**

Assert unrelated user cannot access location histories and receives masked location in archive list. Assert PIC and department head can access.

- [ ] **Step 6: Run tests**

```powershell
cd api
php artisan test --filter=ConfidentialArchiveApprovalTest
php artisan test
```

### Task 8: Manual QA Checklist

- [ ] Run migrations.
- [ ] Create/edit user with each level.
- [ ] Set department head in Department settings.
- [ ] Upload normal archive.
- [ ] Upload confidential archive.
- [ ] Request OTP for both archives.
- [ ] Approve normal archive as PIC and verify OTP appears immediately.
- [ ] Approve confidential archive as PIC and verify it moves to department stage.
- [ ] Approve confidential archive as department head and verify OTP appears.
- [ ] Verify confidential location is hidden from unrelated users.
- [ ] Verify PIC and department head can see and move confidential location.
- [ ] Verify download/preview only works after final OTP verification.

---

## Suggested Commit Sequence

1. `feat: add user levels and department heads`
2. `feat: mark confidential archives`
3. `feat: add two-layer confidential OTP approval`
4. `feat: restrict confidential archive location access`
5. `test: cover confidential approval and location rules`

---

## Open Decisions Before Implementation

1. Should department head be based on the PIC's department, the archive category department, or the requester's department? This plan uses the PIC's department because the archive already has `pic_user_id` and the request says PIC plus kepala departemen.
2. Should `direksi` bypass department-head approval? This plan does not give bypass based on `level`; `role=root/admin` remains the bypass path.
3. Should Confidential also be created as a row in the existing `tags` table? This plan treats the UI badge as enough for security; optional tag sync can be added if search/filter by tag is required.
