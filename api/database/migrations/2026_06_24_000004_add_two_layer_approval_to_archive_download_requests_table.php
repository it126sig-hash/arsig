<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::table('archive_download_requests', function (Blueprint $table) {
            $table->dropForeign(['pic_approved_by_user_id']);
            $table->dropForeign(['department_approved_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropColumn([
                'requires_department_approval',
                'approval_stage',
                'pic_approved_by_user_id',
                'pic_approved_at',
                'department_approved_by_user_id',
                'department_approved_at',
                'rejected_by_user_id',
                'rejected_at',
            ]);
        });
    }
};
