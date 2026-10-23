<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('last_ticket_number')->default(0);
            $table->unsignedInteger('board_version')->default(0);
            $table->unsignedInteger('workflow_version')->default(0);
        });
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 80);
            $table->string('category', 20);
            $table->unsignedInteger('position');
            $table->boolean('is_initial');
            $table->timestamps();
            $table->unique(['project_id', 'code']);
            $table->unique(['project_id', 'id']);
            $table->index(['project_id', 'position']);
        });
        Schema::create('project_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('from_status_id');
            $table->unsignedBigInteger('to_status_id');
            $table->foreign(['project_id', 'from_status_id'], 'transition_from_scope')->references(['project_id', 'id'])->on('project_statuses')->cascadeOnDelete();
            $table->foreign(['project_id', 'to_status_id'], 'transition_to_scope')->references(['project_id', 'id'])->on('project_statuses')->cascadeOnDelete();
            $table->unique(['project_id', 'from_status_id', 'to_status_id'], 'unique_project_transition');
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->string('priority', 20);
            $table->unsignedBigInteger('status_id');
            $table->unsignedInteger('sort_order');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign(['project_id', 'status_id'], 'ticket_status_scope')->references(['project_id', 'id'])->on('project_statuses')->restrictOnDelete();
            $table->unique(['project_id', 'number']);
            $table->index(['project_id', 'status_id', 'sort_order']);
            $table->index(['project_id', 'assignee_id']);
        });
        // Keep historical migrations independent of application services/configuration.
        DB::table('projects')->orderBy('id')->chunkById(100, function ($projects) {
            foreach ($projects as $p) {
                $ids = [];
                $definitions = [['todo', 'To Do', 'open'], ['in_progress', 'In Progress', 'active'], ['in_review', 'In Review', 'active'], ['done', 'Done', 'done']];
                foreach ($definitions as $position => [$code,$name,$category]) {
                    $ids[$code] = DB::table('project_statuses')->insertGetId(['project_id' => $p->id, 'code' => $code, 'name' => $name, 'category' => $category, 'position' => $position, 'is_initial' => $position === 0, 'created_at' => now(), 'updated_at' => now()]);
                }
                foreach ([['todo', 'in_progress'], ['in_progress', 'todo'], ['in_progress', 'in_review'], ['in_review', 'in_progress'], ['in_review', 'done'], ['done', 'todo']] as [$from,$to]) {
                    DB::table('project_status_transitions')->insert(['project_id' => $p->id, 'from_status_id' => $ids[$from], 'to_status_id' => $ids[$to]]);
                }
            }
        });
        DB::table('roles')->where('is_system', true)->whereIn('code', ['manager', 'member'])->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $grants = $role->code === 'manager' ? ['tickets.view', 'tickets.create', 'tickets.edit', 'tickets.delete', 'tickets.setup'] : ['tickets.view', 'tickets.create', 'tickets.edit'];
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values(array_unique([...json_decode($role->permissions, true), ...$grants])), JSON_THROW_ON_ERROR)]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('project_status_transitions');
        Schema::dropIfExists('project_statuses');
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['last_ticket_number', 'board_version', 'workflow_version']));
        DB::table('roles')->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values(array_filter(json_decode($role->permissions, true), fn ($key) => ! str_starts_with($key,'tickets.'))), JSON_THROW_ON_ERROR)]);
            }
        });
    }
};
