<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const LEGACY_GRANTS = [
        'members.invite' => ['members.create'],
        'members.update' => ['members.edit'],
        'members.remove' => ['members.delete'],
        'members.view' => ['members.view', 'activity.view'],
        'roles.manage' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.setup'],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'code')) {
            Schema::table('roles', fn (Blueprint $table) => $table->string('code', 100)->nullable());
        }
        DB::table('roles')->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $code = $role->code;
                if (! $code) {
                    $base = Str::slug($role->name);
                    if (! $base || ! preg_match('/^[a-z]/', $base) || $base === 'system-admin') {
                        $base = 'role-'.$role->id;
                    }
                    $base = rtrim(substr($base, 0, 80), '-');
                    $code = $base;
                    $suffix = 1;
                    while (DB::table('roles')->where('workspace_id', $role->workspace_id)->where('code', $code)->where('id', '!=', $role->id)->exists()) {
                        $code = $base.'-'.++$suffix;
                    }
                }
                $permissions = [];
                foreach (json_decode($role->permissions, true) ?? [] as $key) {
                    foreach (self::LEGACY_GRANTS[$key] ?? [$key] as $grant) {
                        $permissions[] = $grant;
                    }
                }
                DB::table('roles')->where('id', $role->id)->update(['code' => $code, 'permissions' => json_encode(array_values(array_unique($permissions)))]);
            }
        });
        $requireCode = fn () => Schema::table('roles', fn (Blueprint $table) => $table->string('code', 100)->nullable(false)->change());
        if (DB::getDriverName() === 'sqlite') {
            // SQLite rebuilds the referenced table when changing column nullability.
            Schema::withoutForeignKeyConstraints($requireCode);
        } else {
            $requireCode();
        }
        if (! Schema::hasIndex('roles', ['workspace_id', 'code'], 'unique')) {
            Schema::table('roles', fn (Blueprint $table) => $table->unique(['workspace_id', 'code']));
        }
    }

    public function down(): void
    {
        // Coarse legacy grants are restored only when every corresponding action is present.
        DB::table('roles')->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $permissions = json_decode($role->permissions, true) ?? [];
                foreach (self::LEGACY_GRANTS as $legacy => $expanded) {
                    if (array_diff($expanded, $permissions) === []) {
                        $permissions = array_values(array_diff($permissions, $expanded));
                        $permissions[] = $legacy;
                    }
                }
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
