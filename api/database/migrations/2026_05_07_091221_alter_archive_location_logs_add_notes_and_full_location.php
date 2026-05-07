<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('archive_location_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('archive_location_logs', 'notes')) {
                $table->text('notes')->nullable()->after('new_cabinet_slot_id');
            }
            
            // Full old location details
            if (!Schema::hasColumn('archive_location_logs', 'old_floor_id')) {
                $table->foreignId('old_floor_id')->nullable()->after('user_id')->constrained('floors')->nullOnDelete();
            }
            if (!Schema::hasColumn('archive_location_logs', 'old_room_id')) {
                $table->foreignId('old_room_id')->nullable()->after('old_floor_id')->constrained('rooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('archive_location_logs', 'old_cabinet_id')) {
                $table->foreignId('old_cabinet_id')->nullable()->after('old_room_id')->constrained('cabinets')->nullOnDelete();
            }
            
            // Full new location details
            if (!Schema::hasColumn('archive_location_logs', 'new_floor_id')) {
                $table->foreignId('new_floor_id')->nullable()->after('old_cabinet_slot_id')->constrained('floors')->nullOnDelete();
            }
            if (!Schema::hasColumn('archive_location_logs', 'new_room_id')) {
                $table->foreignId('new_room_id')->nullable()->after('new_floor_id')->constrained('rooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('archive_location_logs', 'new_cabinet_id')) {
                $table->foreignId('new_cabinet_id')->nullable()->after('new_room_id')->constrained('cabinets')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_location_logs', function (Blueprint $table) {
            $table->dropForeign(['old_floor_id']);
            $table->dropForeign(['old_room_id']);
            $table->dropForeign(['old_cabinet_id']);
            $table->dropForeign(['new_floor_id']);
            $table->dropForeign(['new_room_id']);
            $table->dropForeign(['new_cabinet_id']);
            
            $table->dropColumn([
                'notes',
                'old_floor_id',
                'old_room_id',
                'old_cabinet_id',
                'new_floor_id',
                'new_room_id',
                'new_cabinet_id'
            ]);
        });
    }
};
