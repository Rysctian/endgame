<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Search,
    FolderKanban,
    ArrowUpRight,
    Grid2X2,
    SlidersHorizontal,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout.vue';
import ProjectForm from '@/features/projects/project-form.vue';
import OptionSelect from '@/features/projects/option-select.vue';
import PaginationLinks from '@/features/user-access/pagination-links.vue';
import type { Paginated } from '@/features/user-access/types';
import type {
    Option,
    ProjectSummary,
    ProjectWorkspace,
    ProjectFormData,
} from '@/features/projects/types';
const props = defineProps<
    ProjectFormData & {
        workspace: ProjectWorkspace;
        projects: Paginated<ProjectSummary>;
        filters: { search: string; status: string };
        statusOptions: Option[];
        can: { create: boolean; view: boolean };
    }
>();
const createOpen = ref(false);
const filters = reactive({
    search: props.filters.search,
    status: props.filters.status || 'all',
});
const base = '/workspaces/' + props.workspace.id + '/projects';
function filter() {
    router.get(
        base,
        {
            search: filters.search,
            status: filters.status === 'all' ? '' : filters.status,
        },
        { preserveState: true, replace: true },
    );
}
</script>
<template>
    <AppLayout title="Projects"
        ><div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="mb-2 text-xs text-muted-foreground">
                    {{ workspace.name }}
                </p>
                <h1>Projects</h1>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ projects.total }}
                    {{ projects.total === 1 ? 'project' : 'projects' }}
                </p>
            </div>
            <Button v-if="can.create" @click="createOpen = true"
                ><Plus class="size-3.5" />Create project</Button
            >
        </div>
        <form
            v-if="can.view"
            class="flex flex-wrap items-center gap-2 border-b pb-5"
            @submit.prevent="filter"
        >
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute left-3 top-2.5 size-3.5 text-muted-foreground"
                /><label for="project-search" class="sr-only"
                    >Search projects</label
                ><Input
                    id="project-search"
                    v-model="filters.search"
                    maxlength="255"
                    placeholder="Search projects"
                    class="pl-9"
                />
            </div>
            <div class="w-40">
                <OptionSelect
                    id="project-status"
                    v-model="filters.status"
                    :options="[
                        { value: 'all', label: 'All statuses' },
                        ...statusOptions,
                    ]"
                    label="Project status"
                />
            </div>
            <Button type="submit" variant="outline"
                ><SlidersHorizontal class="size-3.5" />Apply filters</Button
            ><span
                class="ml-auto hidden items-center gap-1.5 rounded-md bg-muted px-2.5 py-1.5 text-[11px] text-muted-foreground sm:flex"
                ><Grid2X2 class="size-3" />Grid</span
            >
        </form>
        <section
            v-if="projects.data.length"
            aria-label="Project list"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="base + '/' + project.id"
                :aria-label="project.name"
                class="block h-full"
                ><Card class="project-card group h-full"
                    ><div class="flex items-center gap-2.5">
                        <span class="project-symbol size-8 shrink-0"
                            ><FolderKanban class="size-4" :stroke-width="1.6"
                        /></span>
                        <h2
                            class="min-w-0 flex-1 truncate text-[13px] font-semibold"
                        >
                            {{ project.name }}
                        </h2>
                        <Badge
                            variant="outline"
                            class="state-badge"
                            :data-state="project.status"
                            >{{
                                statusOptions.find(
                                    (o) => o.value === project.status,
                                )?.label
                            }}</Badge
                        >
                    </div>
                    <p
                        class="my-5 min-h-10 line-clamp-2 text-xs leading-5 text-muted-foreground"
                    >
                        {{
                            project.description ||
                            'A space to bring your next project together.'
                        }}
                    </p>
                    <div
                        class="mt-auto flex items-center justify-between border-t pt-3 text-[10px] text-muted-foreground"
                    >
                        <span class="font-mono tracking-wide">{{
                            project.key
                        }}</span
                        ><span
                            class="flex items-center gap-1.5 group-hover:text-primary"
                            >Open project<ArrowUpRight class="size-3"
                        /></span></div></Card
            ></Link>
        </section>
        <div v-else class="empty-state">
            <span class="empty-symbol"
                ><FolderKanban class="size-5" :stroke-width="1.5"
            /></span>
            <h2 class="text-sm font-medium">No projects found.</h2>
            <p class="mt-2 max-w-xs text-xs leading-5 text-muted-foreground">
                {{
                    filters.search || filters.status !== 'all'
                        ? 'Try another search or status.'
                        : 'Your next project starts here.'
                }}
            </p>
        </div>
        <PaginationLinks :links="projects.links" /><ProjectForm
            v-if="can.create"
            v-model:open="createOpen"
            :workspace-id="workspace.id"
            :contract="props"
    /></AppLayout>
</template>
