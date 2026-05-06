<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cabinet_slot_tags');

        Schema::create('cabinet_slot_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_slot_id')->constrained('cabinet_slots')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cabinet_slot_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabinet_slot_tag');

        Schema::create('cabinet_slot_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_slot_id')->constrained('cabinet_slots')->cascadeOnDelete();
            $table->string('tag', 100);
            $table->timestamps();
        });
    }
};
