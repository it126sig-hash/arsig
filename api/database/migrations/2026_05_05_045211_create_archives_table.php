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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_number', 100)->nullable();
            $table->enum('archive_type', ['full', 'physical_only', 'placeholder']);
            $table->enum('privacy_type', ['public', 'private', 'specific_user', 'specific_department']);
            $table->enum('download_policy', ['direct_download', 'request_to_pic']);
            $table->enum('status', ['active', 'archived', 'expired'])->default('active');
            $table->foreignId('pic_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path', 500)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->date('reminder_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
