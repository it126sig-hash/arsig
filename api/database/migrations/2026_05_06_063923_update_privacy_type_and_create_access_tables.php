<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update ENUM for privacy_type
        // Old: ['public', 'private', 'specific_user', 'specific_department']
        // New: ['public', 'private', 'department', 'user']
        DB::statement("ALTER TABLE archives MODIFY COLUMN privacy_type ENUM('public', 'private', 'department', 'user') NOT NULL");

        Schema::create('archive_department_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('archive_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_user_access');
        Schema::dropIfExists('archive_department_access');

        // Revert ENUM
        DB::statement("ALTER TABLE archives MODIFY COLUMN privacy_type ENUM('public', 'private', 'specific_user', 'specific_department') NOT NULL");
    }
};
