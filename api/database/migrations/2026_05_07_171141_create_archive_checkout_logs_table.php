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
        Schema::create('archive_checkout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->onDelete('cascade');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->enum('action', ['checkout', 'checkin']);
            $table->string('borrower_name', 255)->nullable();
            $table->text('reason')->nullable();
            $table->date('planned_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_checkout_logs');
    }
};
