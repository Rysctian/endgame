<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout.vue';
import ProjectForm from '@/features/projects/project-form.vue';
import MemberForm from '@/features/projects/member-form.vue';
import FormDialog from '@/features/user-access/form-dialog.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import PaginationLinks from '@/features/user-access/pagination-links.vue';
import type { Paginated } from '@/features/user-access/types';
import type {
    ProjectDetails,
    ProjectWorkspace,
    ProjectMember,
    ProjectFormData,
    Option,
} from '@/features/projects/types';
const props = defineProps<
    ProjectFormData & {
        workspace: ProjectWorkspace;
        project: ProjectDetails;
        statusLabel: string;
        members: Paginated<ProjectMember>;
        memberOptions: Option[];
        memberSearch: string;
        can: {
            edit: boolean;
            archive: boolean;
            restore: boolean;
            manageMembers: boolean;
        };
    }
>();
const editOpen = ref(false),
    memberOpen = ref(false),
    confirmOpen = ref(false);
const action = ref<{
    url: string;
    method: 'post' | 'delete';
    title: string;
    description: string;
    button: string;
}>();
const form = useForm({});
const base = computed(
    () => '/workspaces/' + props.workspace.id + '/projects/' + props.project.id,
);
function ask(
    url: string,
    method: 'post' | 'delete',
    title: string,
    description: string,
    button: string,
) {
    action.value = { url, method, title, description, button };
    form.clearErrors();
    confirmOpen.value = true;
}
function confirm() {
    if (!action.value) return;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
        },
    };
    if (action.value.method === 'delete')
        form.delete(action.value.url, options);
    else form.post(action.value.url, options);
}
</script>
<template>
    <AppLayout :title="project.name">
        <nav
            class="flex gap-2 text-sm text-muted-foreground"
            aria-label="Breadcrumb"
        >
            <Link
                :href="'/workspaces/' + workspace.id"
                class="hover:underline"
                >{{ workspace.name }}</Link
            ><span aria-hidden="true">/</span>
            <Link
                :href="'/workspaces/' + workspace.id + '/projects'"
                class="hover:underline"
                >Projects</Link
            >
        </nav>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="mb-1 font-mono text-sm text-muted-foreground">
                    {{ project.key }}
                </p>
                <h1 class="break-words text-2xl font-semibold">
                    {{ project.name }}
                </h1>
                <p class="mt-2 text-sm">{{ statusLabel }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="can.edit"
                    variant="outline"
                    @click="editOpen = true"
                    >Edit project</Button
                >
                <Button
                    v-if="can.archive"
                    variant="destructive"
                    @click="
                        ask(
                            base + '/archive',
                            'post',
                            'Archive project',
                            'Archive this project? Its members and history will be preserved.',
                            'Confirm archive',
                        )
                    "
                    >Archive project</Button
                >
                <Button
                    v-if="can.restore"
                    @click="
                        ask(
                            base + '/restore',
                            'post',
                            'Restore project',
                            'Restore this project to active status?',
                            'Confirm restore',
                        )
                    "
                    >Restore project</Button
                >
            </div>
        </div>
        <p
            v-if="fieldStates.name.readOnly"
            class="rounded-lg border bg-muted p-4 text-sm"
        >
            Archived projects are read-only.
        </p>
        <section v-if="project.description" class="rounded-xl border p-5">
            <h2 class="mb-3 font-semibold">{{ fieldLabels.description }}</h2>
            <p class="whitespace-pre-wrap break-words text-sm">
                {{ project.description }}
            </p>
        </section>
        <section class="overflow-hidden rounded-xl border">
            <div class="flex items-center justify-between gap-4 p-5">
                <h2 class="font-semibold">
                    Project members ({{ members.total }})
                </h2>
                <Button v-if="can.manageMembers" @click="memberOpen = true"
                    >Add member</Button
                >
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-y bg-muted/40">
                        <tr>
                            <th>Member</th>
                            <th>Account status</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in members.data" :key="member.id">
                            <td>
                                <p class="font-medium">
                                    {{ member.user.name }}
                                </p>
                                <p class="text-muted-foreground">
                                    {{ member.user.email }}
                                </p>
                            </td>
                            <td>{{ member.user.status }}</td>
                            <td class="text-right">
                                <Button
                                    v-if="can.manageMembers"
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        ask(
                                            base + '/members/' + member.id,
                                            'delete',
                                            'Remove project member',
                                            'Remove ' +
                                                member.user.name +
                                                ' from this project?',
                                            'Confirm removal',
                                        )
                                    "
                                    >Remove member</Button
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p
                v-if="!members.data.length"
                class="p-5 text-sm text-muted-foreground"
            >
                No project members yet.
            </p>
            <div class="px-5 pb-4">
                <PaginationLinks :links="members.links" />
            </div>
        </section>
        <ProjectForm
            v-if="can.edit"
            v-model:open="editOpen"
            :workspace-id="workspace.id"
            :project-id="project.id"
            :contract="props"
        />
        <MemberForm
            v-if="can.manageMembers"
            v-model:open="memberOpen"
            :url="base"
            :options="memberOptions"
            :search="memberSearch"
        />
        <FormDialog
            v-model:open="confirmOpen"
            :title="action?.title ?? 'Confirm action'"
            :description="action?.description"
        >
            <form class="space-y-4" @submit.prevent="confirm">
                <FormErrors :errors="form.errors" /><Button
                    type="submit"
                    :disabled="form.processing"
                    :variant="
                        action?.method === 'delete' || can.archive
                            ? 'destructive'
                            : 'default'
                    "
                    >{{ action?.button }}</Button
                >
            </form>
        </FormDialog>
    </AppLayout>
</template>
