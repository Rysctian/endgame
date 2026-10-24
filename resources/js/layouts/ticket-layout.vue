<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Columns3,
    List,
    Table2,
    LayoutGrid,
    Settings2,
    FolderKanban,
} from '@lucide/vue';
import AppShell from './app-shell.vue';
const props = defineProps<{
    workspace: { id: number; name: string };
    project: { id: number; name: string; key: string; status: string };
    active: string;
    canSetup?: boolean;
    canProject?: boolean;
    canView?: boolean;
}>();
const base = computed(
    () => '/workspaces/' + props.workspace.id + '/projects/' + props.project.id,
);
const projectNav = computed(() => ({
    overview: !!props.canProject,
    tickets: props.canView !== false,
    workflow: !!props.canSetup,
    active: props.active,
}));
const tabs = computed(() =>
    [
        {
            key: 'overview',
            name: 'Overview',
            icon: LayoutGrid,
            url: base.value,
            visible: props.canProject,
        },
        {
            key: 'board',
            name: 'Board',
            icon: Columns3,
            url: base.value + '/tickets?view=board',
            visible: props.canView !== false,
        },
        {
            key: 'list',
            name: 'List',
            icon: List,
            url: base.value + '/tickets?view=list',
            visible: props.canView !== false,
        },
        {
            key: 'table',
            name: 'Table',
            icon: Table2,
            url: base.value + '/tickets?view=table',
            visible: props.canView !== false,
        },
        {
            key: 'workflow',
            name: 'Workflow',
            icon: Settings2,
            url: base.value + '/workflow',
            visible: props.canSetup,
        },
    ].filter((t) => t.visible),
);
</script>
<template>
    <AppShell
        :title="project.name"
        :workspace="workspace"
        :project="project"
        :can-projects="canProject"
        :project-nav="projectNav"
        ><template #breadcrumbs
            ><Link
                :href="
                    canProject
                        ? '/workspaces/' + workspace.id + '/projects'
                        : '/workspaces/' + workspace.id
                "
                class="text-muted-foreground hover:text-foreground"
                >{{ canProject ? 'Projects' : workspace.name }}</Link
            ><span class="mx-1 text-muted-foreground/40">/</span
            ><span class="truncate font-medium">{{
                project.name
            }}</span></template
        >
        <div class="ticket-workspace">
            <div class="project-header px-4 pt-6 sm:px-7">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="project-symbol size-10 shrink-0 rounded-xl"
                        ><FolderKanban class="size-5" :stroke-width="1.7"
                    /></span>
                    <div class="min-w-0">
                        <h1
                            class="break-words text-xl font-semibold tracking-tight"
                        >
                            {{ project.name }}
                        </h1>
                        <span
                            class="font-mono text-[10px] tracking-wide text-muted-foreground"
                            >{{ project.key }}</span
                        >
                    </div>
                    <Badge
                        variant="outline"
                        class="state-badge"
                        :data-state="project.status"
                        >{{
                            project.status === 'active' ? 'Active' : 'Archived'
                        }}</Badge
                    >
                    <div class="ml-auto flex flex-wrap gap-2">
                        <slot name="actions" />
                    </div>
                </div>
                <nav
                    aria-label="Project views"
                    class="mt-5 flex gap-6 overflow-x-auto"
                >
                    <Link
                        v-for="tab in tabs"
                        :key="tab.key"
                        :href="tab.url"
                        class="project-tab"
                        :class="active === tab.key ? 'is-active' : ''"
                        :aria-current="active === tab.key ? 'page' : undefined"
                        ><component :is="tab.icon" class="size-3.5" />{{
                            tab.name
                        }}</Link
                    >
                </nav>
            </div>
            <div class="min-w-0 border-t">
                <p
                    v-if="project.status === 'archived'"
                    class="border-b bg-muted/60 px-7 py-3 text-xs text-muted-foreground"
                >
                    Archived projects are read-only.
                </p>
                <slot />
            </div></div
    ></AppShell>
</template>
