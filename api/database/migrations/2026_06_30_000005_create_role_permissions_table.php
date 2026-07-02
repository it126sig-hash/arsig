<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MODULES = [
        'companies', 'departments', 'floors', 'rooms',
        'cabinets', 'cabinet_slots', 'categories', 'tags', 'users',
    ];

    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['admin', 'user']);
            $table->string('module', 32);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            $table->unique(['role', 'module']);
        });

        $now = now();
        $rows = [];

        foreach (self::MODULES as $module) {
            $rows[] = [
                'role' => 'admin',
                'module' => $module,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (self::MODULES as $module) {
            $isTags = $module === 'tags';
            $rows[] = [
                'role' => 'user',
                'module' => $module,
                'can_view' => $isTags,
                'can_create' => $isTags,
                'can_update' => false,
                'can_delete' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('role_permissions')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
