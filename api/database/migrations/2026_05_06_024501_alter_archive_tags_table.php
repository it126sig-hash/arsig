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
        Schema::table('archive_tags', function (Blueprint $table) {
            if (Schema::hasColumn('archive_tags', 'name')) {
                $table->dropColumn('name');
            }

            $table->foreignId('tag_id')->after('archive_id')->constrained('tags')->cascadeOnDelete();
            $table->unique(['archive_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_tags', function (Blueprint $table) {
            $table->dropUnique(['archive_id', 'tag_id']);
            $table->dropConstrainedForeignId('tag_id');
            $table->string('name')->after('archive_id');
        });
    }
};
