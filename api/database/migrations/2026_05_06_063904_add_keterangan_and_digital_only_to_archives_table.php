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
        Schema::table('archives', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('name');
        });

        // SQLite used by tests stores enum columns as text and does not support MODIFY COLUMN.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE archives MODIFY COLUMN archive_type ENUM('full', 'physical_only', 'digital_only', 'placeholder') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });

        // Revert ENUM (placeholder only includes what was there before)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE archives MODIFY COLUMN archive_type ENUM('full', 'physical_only', 'placeholder') NOT NULL");
        }
    }
};
