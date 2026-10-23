<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import {
    PanelsTopLeft,
    LayoutDashboard,
    Columns3,
    List,
    Table2,
    Settings2,
    Users,
    Menu,
    X,
    LogOut,
    ChevronRight,
    FolderKanban,
} from '@lucide/vue';
import type { Project, Workspace } from '@/features/tickets/types';
const props = defineProps<{
    workspace: Workspace;
    project: Project;
    active: string;
    canSetup?: boolean;
    canProject?: boolean;
    canView?: boolean;
}>();
const base = computed(
    () => '/workspaces/' + props.workspace.id + '/projects/' + props.project.id,
);
const mobileOpen = ref(false);
const page = usePage<{
    auth: { user: { name: string; is_system_admin: boolean } };
}>();
const nav = computed(() => [
    {
        name: 'Board',
        icon: Columns3,
        href: base.value + '/tickets?view=board',
        key: 'board',
    },
    {
        name: 'List',
        icon: List,
        href: base.value + '/tickets?view=list',
        key: 'list',
    },
    {
        name: 'Table',
        icon: Table2,
        href: base.value + '/tickets?view=table',
        key: 'table',
    },
]);
</script>
<template>
    <Head :title="project.name + ' · ' + active" />
    <div class="ticket-workspace min-h-dvh bg-card text-foreground">
        <button
            v-if="mobileOpen"
            class="fixed inset-0 z-30 bg-overlay lg:hidden"
            aria-label="Close navigation"
            @click="mobileOpen = false"
        />
        <aside
            :class="
                mobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0'
            "
            class="fixed inset-y-0 left-0 z-40 flex w-[var(--workspace-sidebar-width)] flex-col border-r bg-sidebar px-3 py-4"
        >
            <div class="mb-7 flex items-center gap-2 px-2">
                <Link
                    href="/workspaces"
                    class="flex size-7 items-center justify-center rounded-md bg-foreground text-xs font-bold text-background"
                    >E</Link
                ><span class="min-w-0 flex-1 truncate font-semibold">{{
                    workspace.name
                }}</span
                ><button
                    class="ticket-icon lg:hidden"
                    aria-label="Close navigation"
                    @click="mobileOpen = false"
                >
                    <X class="size-4" />
                </button>
            </div>
            <nav
                aria-label="Workspace navigation"
                class="space-y-1 text-sidebar-foreground"
            >
                <Link href="/workspaces" class="ticket-nav"
                    ><LayoutDashboard class="size-4" />Workspaces</Link
                ><Link :href="'/workspaces/' + workspace.id" class="ticket-nav"
                    ><Users class="size-4" />Members & access</Link
                ><Link
                    :href="
                        canProject
                            ? '/workspaces/' + workspace.id + '/projects'
                            : '/workspaces/' + workspace.id
                    "
                    class="ticket-nav"
                    ><FolderKanban class="size-4" />Projects</Link
                >
                <p
                    class="px-2 pb-2 pt-7 text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                >
                    Project
                </p>
                <div
                    class="ticket-nav border bg-card font-medium text-foreground"
                >
                    <span
                        class="flex size-5 items-center justify-center rounded bg-primary/10 text-primary"
                        ><PanelsTopLeft class="size-3.5" /></span
                    ><span class="truncate">{{ project.name }}</span>
                </div>
                <div class="ml-4 border-l pl-3">
                    <template v-if="canView !== false"
                        ><Link
                            v-for="item in nav"
                            :key="item.key"
                            :href="item.href"
                            :aria-label="item.name + ' navigation'"
                            class="ticket-nav"
                            :class="
                                active === item.key
                                    ? 'bg-sidebar-accent text-foreground'
                                    : ''
                            "
                            ><component :is="item.icon" class="size-3.5" />{{
                                item.name
                            }}</Link
                        ></template
                    ><Link
                        v-if="canSetup"
                        :href="base + '/workflow'"
                        aria-label="Workflow navigation"
                        class="ticket-nav"
                        :class="
                            active === 'workflow'
                                ? 'bg-sidebar-accent text-foreground'
                                : ''
                        "
                        ><Settings2 class="size-3.5" />Workflow</Link
                    >
                </div>
            </nav>
            <div class="mt-auto space-y-1 border-t pt-3">
                <Link href="/profile" class="ticket-nav"
                    ><span class="ticket-avatar">{{
                        page.props.auth.user.name.slice(0, 2).toUpperCase()
                    }}</span
                    ><span class="truncate">{{
                        page.props.auth.user.name
                    }}</span></Link
                ><button
                    class="ticket-nav w-full"
                    @click="router.post('/logout')"
                >
                    <LogOut class="size-4" />Sign out
                </button>
            </div>
        </aside>
        <div class="min-w-0 lg:ml-[var(--workspace-sidebar-width)]">
            <header
                class="flex h-[var(--workspace-topbar-height)] items-center gap-2 border-b px-4 lg:px-6"
            >
                <button
                    class="ticket-icon lg:hidden"
                    aria-label="Open navigation"
                    @click="mobileOpen = true"
                >
                    <Menu class="size-4" /></button
                ><Link
                    :href="
                        canProject
                            ? '/workspaces/' + workspace.id + '/projects'
                            : '/workspaces/' + workspace.id
                    "
                    class="text-xs text-muted-foreground hover:text-foreground"
                    >Projects</Link
                ><ChevronRight class="size-3 text-muted-foreground" /><span
                    class="truncate text-xs"
                    >{{ project.name }}</span
                >
            </header>
            <div class="px-4 pt-5 lg:px-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <PanelsTopLeft class="size-5" />
                    </div>
                    <h1 class="min-w-0 break-words text-lg font-semibold">
                        {{ project.name }}
                    </h1>
                    <span
                        class="rounded border px-2 py-0.5 text-xs text-muted-foreground"
                        >{{
                            project.status === 'active' ? 'Active' : 'Archived'
                        }}</span
                    ><span
                        class="ml-auto font-mono text-xs text-muted-foreground"
                        >{{ project.key }}</span
                    ><slot name="actions" />
                </div>
                <nav
                    aria-label="Project views"
                    class="mt-4 flex gap-5 overflow-x-auto text-xs"
                >
                    <Link v-if="canProject" :href="base" class="ticket-tab"
                        ><LayoutDashboard class="size-3.5" />Overview</Link
                    ><template v-if="canView !== false"
                        ><Link
                            v-for="item in nav"
                            :key="item.key"
                            :href="item.href"
                            class="ticket-tab"
                            :class="
                                active === item.key
                                    ? 'border-foreground text-foreground'
                                    : 'border-transparent text-muted-foreground'
                            "
                            :aria-current="
                                active === item.key ? 'page' : undefined
                            "
                            ><component :is="item.icon" class="size-3.5" />{{
                                item.name
                            }}</Link
                        ></template
                    ><Link
                        v-if="canSetup"
                        :href="base + '/workflow'"
                        class="ticket-tab"
                        :class="
                            active === 'workflow'
                                ? 'border-foreground text-foreground'
                                : 'border-transparent text-muted-foreground'
                        "
                        ><Settings2 class="size-3.5" />Workflow</Link
                    >
                </nav>
            </div>
            <main class="min-w-0 border-t">
                <p
                    v-if="project.status === 'archived'"
                    class="border-b bg-muted px-6 py-3 text-sm"
                >
                    Archived projects are read-only.
                </p>
                <slot />
            </main>
        </div>
        <Toaster rich-colors close-button position="bottom-right" />
    </div>
</template>
