<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import TicketLayout from '@/layouts/ticket-layout.vue';
import {
    UsersRound,
    CalendarDays,
    Hash,
    Plus,
    Pencil,
    Archive,
    RotateCcw,
} from '@lucide/vue';
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
            tickets: boolean;
            workflow: boolean;
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
    <TicketLayout
        :workspace="workspace"
        :project="project"
        active="overview"
        :can-project="true"
        :can-view="can.tickets"
        :can-setup="can.workflow"
        ><template #actions
            ><Button v-if="can.edit" variant="outline" @click="editOpen = true"
                ><Pencil class="size-3.5" />Edit project</Button
            ><Button
                v-if="can.archive"
                variant="ghost"
                @click="
                    ask(
                        base + '/archive',
                        'post',
                        'Archive project',
                        'Archive this project? Its members and history will be preserved.',
                        'Confirm archive',
                    )
                "
                ><Archive class="size-3.5" />Archive project</Button
            ><Button
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
                ><RotateCcw class="size-3.5" />Restore project</Button
            ></template
        >
        <div
            class="page-content mx-auto grid max-w-6xl items-start gap-5 px-4 py-6 sm:px-7 lg:grid-cols-[minmax(0,1fr)_300px]"
        >
            <div class="min-w-0 space-y-5">
                <section class="surface p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="section-title">About</h2>
                    </div>
                    <p
                        class="whitespace-pre-wrap break-words text-xs leading-6 text-muted-foreground"
                    >
                        {{ project.description || 'No description yet.' }}
                    </p>
                </section>
                <section class="surface">
                    <div class="section-heading">
                        <h2 class="section-title">
                            Project members
                            <span
                                class="ml-1.5 text-xs font-normal text-muted-foreground"
                                >{{ members.total }}</span
                            >
                        </h2>
                        <Button
                            v-if="can.manageMembers"
                            size="sm"
                            variant="outline"
                            @click="memberOpen = true"
                            ><Plus class="size-3.5" />Add member</Button
                        >
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/40">
                                <tr>
                                    <th>Member</th>
                                    <th>Account status</th>
                                    <th>
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="member in members.data"
                                    :key="member.id"
                                >
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <span class="avatar-token size-8">{{
                                                member.user.name
                                                    .split(' ')
                                                    .map((n) => n[0])
                                                    .slice(0, 2)
                                                    .join('')
                                            }}</span>
                                            <div>
                                                <p class="font-medium">
                                                    {{ member.user.name }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                                >
                                                    {{ member.user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="state-badge"
                                            :data-state="member.user.status"
                                            >{{ member.user.status }}</span
                                        >
                                    </td>
                                    <td class="text-right">
                                        <Button
                                            v-if="can.manageMembers"
                                            variant="ghost"
                                            size="sm"
                                            @click="
                                                ask(
                                                    base +
                                                        '/members/' +
                                                        member.id,
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
                        class="px-5 py-8 text-center text-xs text-muted-foreground"
                    >
                        No project members yet.
                    </p>
                    <div class="px-5 py-3">
                        <PaginationLinks :links="members.links" />
                    </div>
                </section>
            </div>
            <aside class="surface p-5">
                <h2 class="mb-5 section-title">Details</h2>
                <dl class="space-y-5 text-xs">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Status</dt>
                        <dd>
                            <span
                                class="state-badge"
                                :data-state="project.status"
                                >{{ statusLabel }}</span
                            >
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Hash class="size-3.5" />Project key
                        </dt>
                        <dd class="font-mono">{{ project.key }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <UsersRound class="size-3.5" />Members
                        </dt>
                        <dd>{{ members.total }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <CalendarDays class="size-3.5" />Created
                        </dt>
                        <dd>
                            {{
                                new Date(project.created_at).toLocaleDateString(
                                    undefined,
                                    {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                    },
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
        <ProjectForm
            v-if="can.edit"
            v-model:open="editOpen"
            :workspace-id="workspace.id"
            :project-id="project.id"
            :contract="props"
        /><MemberForm
            v-if="can.manageMembers"
            v-model:open="memberOpen"
            :url="base"
            :options="memberOptions"
            :search="memberSearch"
        /><FormDialog
            v-model:open="confirmOpen"
            :title="action?.title ?? 'Confirm action'"
            :description="action?.description"
            ><form class="space-y-4" @submit.prevent="confirm">
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
            </form></FormDialog
        ></TicketLayout
    >
</template>
