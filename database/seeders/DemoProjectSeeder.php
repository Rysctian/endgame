<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is available only in local/testing environments.');
        }

        DB::transaction(function (): void {
            $workspaces = Workspace::whereIn('slug', ['endgame-hq', 'product-design', 'engineering', 'customer-success', 'operations'])->get();
            $names = ['Website Relaunch', 'Mobile Application', 'Customer Onboarding', 'Internal Tools', 'Support Portal'];
            foreach ($workspaces as $workspace) {
                $members = $workspace->members()->whereHas('user', fn ($q) => $q->where('status', 'active'))->with('user')->orderBy('id')->get();
                $creator = $members->first(fn ($member) => $member->user->hasWorkspacePermission($workspace, 'projects.create'))?->user;
                if (! $creator) {
                    continue;
                }
                foreach ($names as $index => $name) {
                    $project = $workspace->projects()->firstOrCreate(['key' => 'DEMO'.($index + 1)], [
                        'name' => $name,
                        'description' => 'Plan and deliver the '.strtolower($name).' initiative.',
                        'status' => 'active',
                        'created_by' => $creator->id,
                    ]);
                    if (! $project->wasRecentlyCreated) {
                        continue;
                    }
                    ActivityLog::record($creator, 'project.created', $project, $workspace->id, $project->only('name', 'key', 'status'));
                    foreach ($members as $member) {
                        $project->members()->create(['user_id' => $member->user_id]);
                        ActivityLog::record($creator, 'project.member_added', $project, $workspace->id, ['user_id' => $member->user_id]);
                    }
                }
            }
        });
    }
}
