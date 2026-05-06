<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinets', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('name');
            $table->string('door_count', 20)->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('cabinets', function (Blueprint $table) {
            $table->dropColumn(['keterangan', 'door_count']);
        });
    }
};
