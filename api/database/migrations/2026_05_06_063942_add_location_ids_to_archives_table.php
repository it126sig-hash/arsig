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
        Schema::table('archives', function (Blueprint $table) {
            $table->foreignId('floor_id')->nullable()->after('reminder_date')->constrained('floors')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('floor_id')->constrained('rooms')->nullOnDelete();
            $table->foreignId('cabinet_id')->nullable()->after('room_id')->constrained('cabinets')->nullOnDelete();
            $table->foreignId('cabinet_slot_id')->nullable()->after('cabinet_id')->constrained('cabinet_slots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropForeign(['floor_id']);
            $table->dropForeign(['room_id']);
            $table->dropForeign(['cabinet_id']);
            $table->dropForeign(['cabinet_slot_id']);
            $table->dropColumn(['floor_id', 'room_id', 'cabinet_id', 'cabinet_slot_id']);
        });
    }
};
