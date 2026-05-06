<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinet_slots', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('name');
            $table->enum('status', ['aktif', 'nonaktif', 'rusak'])->default('aktif')->after('keterangan');
            $table->dropForeign(['pic_user_id']);
            $table->dropColumn('pic_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('cabinet_slots', function (Blueprint $table) {
            $table->dropColumn(['keterangan', 'status']);
            $table->foreignId('pic_user_id')->constrained('users')->cascadeOnDelete();
        });
    }
};
