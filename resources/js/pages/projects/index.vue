<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
    <AppLayout :title="workspace.name + ' · Projects'">
        <Link
            :href="'/workspaces/' + workspace.id"
            class="text-sm text-muted-foreground hover:underline"
            >{{ workspace.name }}</Link
        >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Projects</h1>
            <Button v-if="can.create" @click="createOpen = true"
                >Create project</Button
            >
        </div>
        <form
            v-if="can.view"
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="filter"
        >
            <div class="min-w-48 flex-1 space-y-2">
                <label for="project-search">Search projects</label
                ><input
                    id="project-search"
                    v-model="filters.search"
                    maxlength="255"
                    placeholder="Name or key"
                />
            </div>
            <div class="min-w-40 space-y-2">
                <label for="project-status">Status</label
                ><OptionSelect
                    id="project-status"
                    v-model="filters.status"
                    :options="[
                        { value: 'all', label: 'All statuses' },
                        ...statusOptions,
                    ]"
                    label="Project status"
                />
            </div>
            <Button type="submit" variant="outline">Apply filters</Button>
        </form>
        <section
            class="overflow-hidden rounded-xl border"
            aria-label="Project list"
        >
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th>Project</th>
                            <th>Key</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="project in projects.data" :key="project.id">
                            <td>
                                <Link
                                    :href="base + '/' + project.id"
                                    class="font-medium hover:underline"
                                    >{{ project.name }}</Link
                                >
                            </td>
                            <td class="font-mono text-sm">{{ project.key }}</td>
                            <td>
                                {{
                                    statusOptions.find(
                                        (option) =>
                                            option.value === project.status,
                                    )?.label
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p
                v-if="!projects.data.length"
                class="p-6 text-sm text-muted-foreground"
            >
                No projects found.
            </p>
        </section>
        <PaginationLinks :links="projects.links" />
        <ProjectForm
            v-if="can.create"
            v-model:open="createOpen"
            :workspace-id="workspace.id"
            :contract="props"
        />
    </AppLayout>
</template>
