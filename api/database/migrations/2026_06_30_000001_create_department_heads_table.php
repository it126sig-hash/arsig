<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'user_id']);
        });

        DB::table('departments')->whereNotNull('head_user_id')->get(['id', 'head_user_id'])->each(function ($department) {
            DB::table('department_heads')->insert([
                'department_id' => $department->id,
                'user_id' => $department->head_user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_user_id']);
            $table->dropColumn('head_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_user_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('department_heads')
            ->orderBy('id')
            ->get(['department_id', 'user_id'])
            ->unique('department_id')
            ->each(function ($head) {
                DB::table('departments')->where('id', $head->department_id)->update(['head_user_id' => $head->user_id]);
            });

        Schema::dropIfExists('department_heads');
    }
};
