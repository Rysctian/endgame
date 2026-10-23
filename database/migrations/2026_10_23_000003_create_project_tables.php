<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 10);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['workspace_id', 'key']);
            $table->index(['workspace_id', 'status']);
        });
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        DB::table('roles')->where('is_system', true)->whereIn('code', ['manager', 'member'])->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $grants = $role->code === 'manager'
                    ? ['projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.setup']
                    : ['projects.view'];
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique([...json_decode($role->permissions, true), ...$grants])), JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        DB::table('roles')->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $permissions = array_values(array_filter(json_decode($role->permissions, true), fn ($key) => ! str_starts_with($key, 'projects.')));
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions, JSON_THROW_ON_ERROR)]);
            }
        });
    }
};
