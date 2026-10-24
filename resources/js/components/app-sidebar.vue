<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Layers2,
    LayoutGrid,
    FolderKanban,
    UsersRound,
    ShieldCheck,
    UserRound,
    LogOut,
    Columns3,
    List,
    Table2,
    Settings2,
    ArrowUpRight,
    Archive,
} from '@lucide/vue';
import type { SharedProps } from '@/features/user-access/types';
const props = defineProps<{
    workspace?: { id: number; name: string };
    project?: { id: number; name: string; key: string };
    canProjects?: boolean;
    projectNav?: {
        overview: boolean;
        tickets: boolean;
        workflow: boolean;
        active: string;
    };
}>();
defineEmits<{ navigate: [] }>();
const page = usePage<SharedProps>();
const base = computed(() =>
    props.workspace ? '/workspaces/' + props.workspace.id : '',
);
const initials = computed(() =>
    (page.props.auth.user?.name ?? 'Endgame')
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join(''),
);
const active = computed(() => page.component);
const navigationGroups = computed(() => {
    const groups = page.props.projectNavigation;
    const hasProjects = groups.some((group) => group.projects.length > 0);
    return groups
        .filter(
            (group) =>
                group.projects.length > 0 ||
                group.id === props.workspace?.id ||
                !hasProjects,
        )
        .sort(
            (a, b) =>
                Number(b.id === props.workspace?.id) -
                Number(a.id === props.workspace?.id),
        );
});
</script>
<template>
    <div class="flex h-full min-h-0 flex-col">
        <Link
            href="/workspaces"
            class="brand-lockup shrink-0"
            @click="$emit('navigate')"
            ><span class="brand-mark"
                ><Layers2 class="size-4" :stroke-width="1.8" /></span
            ><span class="font-semibold tracking-tight">Endgame</span></Link
        >
        <nav
            aria-label="Main navigation"
            class="mt-7 min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-1"
        >
            <Link
                href="/workspaces"
                class="shell-nav"
                :class="active === 'workspaces/index' ? 'is-active' : ''"
                :aria-current="
                    active === 'workspaces/index' ? 'page' : undefined
                "
                @click="$emit('navigate')"
                ><LayoutGrid class="size-4" />Workspaces</Link
            ><Link
                v-if="page.props.auth.user?.is_system_admin"
                href="/admin/users"
                class="shell-nav"
                :class="active === 'admin/users' ? 'is-active' : ''"
                @click="$emit('navigate')"
                ><ShieldCheck class="size-4" />Accounts</Link
            ><template v-if="workspace"
                ><p class="nav-caption">Workspace</p>
                <div class="mb-2 flex items-center gap-2 px-2.5">
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-md border bg-card text-[10px] font-semibold"
                        >{{ workspace.name.slice(0, 2).toUpperCase() }}</span
                    ><span
                        class="truncate text-xs font-medium text-foreground"
                        >{{ workspace.name }}</span
                    >
                </div>
                <Link
                    :href="base"
                    aria-label="Workspace access"
                    class="shell-nav"
                    :class="active === 'workspaces/show' ? 'is-active' : ''"
                    @click="$emit('navigate')"
                    ><UsersRound class="size-4" />Members & access</Link
                ></template
            >
            <section aria-label="Projects navigation" class="pt-5">
                <div
                    class="flex items-center gap-2.5 px-2.5 pb-2 text-xs font-medium text-foreground"
                >
                    <FolderKanban class="size-4 text-muted-foreground" />
                    Projects
                </div>
                <div
                    v-for="group in navigationGroups"
                    :key="group.id"
                    class="mb-3"
                >
                    <Link
                        v-if="group.href"
                        :href="group.href"
                        :aria-label="
                            workspace?.id === group.id
                                ? 'Workspace projects'
                                : group.name + ' projects'
                        "
                        class="shell-nav mb-1"
                        :class="
                            active === 'projects/index' &&
                            workspace?.id === group.id
                                ? 'is-active'
                                : ''
                        "
                        @click="$emit('navigate')"
                    >
                        <LayoutGrid class="size-3.5" />
                        <span class="truncate">{{
                            navigationGroups.length > 1 ||
                            workspace?.id !== group.id
                                ? group.name
                                : 'All projects'
                        }}</span>
                        <span
                            class="ml-auto text-[10px] tabular-nums text-muted-foreground"
                            >{{ group.projects.length }}</span
                        >
                    </Link>
                    <p
                        v-else-if="
                            navigationGroups.length > 1 ||
                            workspace?.id !== group.id
                        "
                        class="px-2.5 py-2 text-[10px] text-muted-foreground"
                    >
                        {{ group.name }}
                    </p>
                    <ul class="space-y-0.5">
                        <li v-for="entry in group.projects" :key="entry.id">
                            <Link
                                :href="entry.href"
                                :aria-label="'Open project ' + entry.name"
                                :title="entry.name + ' (' + entry.key + ')'"
                                class="shell-nav"
                                :class="
                                    workspace?.id === group.id &&
                                    project?.id === entry.id
                                        ? 'is-active'
                                        : ''
                                "
                                :aria-current="
                                    workspace?.id === group.id &&
                                    project?.id === entry.id
                                        ? 'location'
                                        : undefined
                                "
                                @click="$emit('navigate')"
                            >
                                <span class="project-symbol size-5 shrink-0"
                                    ><FolderKanban class="size-3"
                                /></span>
                                <span class="min-w-0 flex-1 truncate">{{
                                    entry.name
                                }}</span>
                                <Archive
                                    v-if="entry.status === 'archived'"
                                    class="size-3"
                                    aria-label="Archived project"
                                />
                            </Link>
                            <div
                                v-if="
                                    workspace?.id === group.id &&
                                    project?.id === entry.id
                                "
                                class="mb-2 ml-4 mt-0.5 space-y-0.5 border-l pl-2"
                            >
                                <Link
                                    v-if="entry.can.overview"
                                    :href="entry.base"
                                    aria-label="Project overview navigation"
                                    class="shell-nav"
                                    :class="
                                        projectNav?.active === 'overview'
                                            ? 'is-active'
                                            : ''
                                    "
                                    @click="$emit('navigate')"
                                    ><LayoutGrid
                                        class="size-3.5"
                                    />Overview</Link
                                >
                                <template v-if="entry.can.tickets">
                                    <Link
                                        v-for="item in [
                                            {
                                                key: 'board',
                                                name: 'Board',
                                                icon: Columns3,
                                            },
                                            {
                                                key: 'list',
                                                name: 'List',
                                                icon: List,
                                            },
                                            {
                                                key: 'table',
                                                name: 'Table',
                                                icon: Table2,
                                            },
                                        ]"
                                        :key="item.key"
                                        :href="
                                            entry.base +
                                            '/tickets?view=' +
                                            item.key
                                        "
                                        :aria-label="item.name + ' navigation'"
                                        class="shell-nav"
                                        :class="
                                            projectNav?.active === item.key
                                                ? 'is-active'
                                                : ''
                                        "
                                        @click="$emit('navigate')"
                                        ><component
                                            :is="item.icon"
                                            class="size-3.5"
                                        />{{ item.name }}</Link
                                    >
                                </template>
                                <Link
                                    v-if="entry.can.workflow"
                                    :href="entry.base + '/workflow'"
                                    aria-label="Workflow navigation"
                                    class="shell-nav"
                                    :class="
                                        projectNav?.active === 'workflow'
                                            ? 'is-active'
                                            : ''
                                    "
                                    @click="$emit('navigate')"
                                    ><Settings2
                                        class="size-3.5"
                                    />Workflow</Link
                                >
                            </div>
                        </li>
                    </ul>
                    <p
                        v-if="!group.projects.length"
                        class="px-2.5 py-2 text-xs text-muted-foreground"
                    >
                        No accessible projects
                    </p>
                </div>
                <p
                    v-if="!navigationGroups.length"
                    class="px-2.5 py-2 text-xs text-muted-foreground"
                >
                    No accessible projects
                </p>
            </section>
        </nav>
        <div class="shrink-0 px-3 pb-4 pt-4">
            <div class="border-t pt-3">
                <Link
                    href="/profile"
                    class="shell-nav"
                    :class="active === 'settings/profile' ? 'is-active' : ''"
                    @click="$emit('navigate')"
                    ><UserRound class="size-4" />Profile</Link
                ><button
                    class="shell-nav w-full"
                    @click="router.post('/logout')"
                >
                    <LogOut class="size-4" />Sign out
                </button>
                <div class="mt-4 flex items-center gap-2.5 px-2">
                    <span class="avatar-token size-8">{{ initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-foreground">
                            {{ page.props.auth.user?.name }}
                        </p>
                        <p class="truncate text-[10px] text-muted-foreground">
                            {{ page.props.auth.user?.email }}
                        </p>
                    </div>
                    <ArrowUpRight
                        class="size-3 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
