<script setup lang="ts">
import { ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout.vue";
import WorkspaceForm from "@/features/user-access/workspace-form.vue";
import RoleForm from "@/features/user-access/role-form.vue";
import MemberForm from "@/features/user-access/member-form.vue";
import InvitationForm from "@/features/user-access/invitation-form.vue";
import FormDialog from "@/features/user-access/form-dialog.vue";
import FormErrors from "@/features/user-access/form-errors.vue";
import PaginationLinks from "@/features/user-access/pagination-links.vue";
import ActivityList from "@/features/user-access/activity-list.vue";
import type {
    Workspace,
    Role,
    RoleOption,
    Member,
    Invitation,
    Paginated,
    Activity,
    PermissionModule,
} from "@/features/user-access/types";
const props = defineProps<{
    workspace: Workspace;
    can: Record<string, boolean>;
    canEdit: boolean;
    members: Paginated<Member> | null;
    roles: Role[];
    roleOptions: RoleOption[];
    permissionModules: PermissionModule[];
    invitations: Paginated<Invitation> | null;
    activities: Paginated<Activity> | null;
    ticketProjects: Paginated<{
        id: number;
        name: string;
        key: string;
        status: string;
    }> | null;
}>();
const workspaceOpen = ref(false),
    roleOpen = ref(false),
    memberOpen = ref(false),
    inviteOpen = ref(false),
    confirmOpen = ref(false);
const selectedRole = ref<Role>(),
    selectedMember = ref<Member>();
const action = ref<{
    url: string;
    title: string;
    description: string;
    button: string;
}>();
const deletion = useForm({});
function remove(
    url: string,
    title: string,
    description: string,
    button: string,
) {
    action.value = { url, title, description, button };
    deletion.clearErrors();
    confirmOpen.value = true;
}
function confirm() {
    if (action.value)
        deletion.delete(action.value.url, {
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
            },
        });
}
const base = "/workspaces/" + props.workspace.id;
</script>
<template>
    <AppLayout :title="workspace.name">
        <Link
            v-if="can['projects.view'] || can['projects.create']"
            :href="'/workspaces/' + workspace.id + '/projects'"
            class="inline-flex text-sm font-medium hover:underline"
            >Projects</Link
        >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="break-words text-2xl font-semibold tracking-tight">
                    {{ workspace.name }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ workspace.slug }}
                </p>
            </div>
            <Button
                v-if="canEdit"
                variant="outline"
                @click="workspaceOpen = true"
                >Edit workspace</Button
            >
        </div>
        <section v-if="ticketProjects" class="rounded-xl border p-5">
            <h2 class="mb-4 font-semibold">Ticket boards</h2>
            <ul class="divide-y">
                <li
                    v-for="project in ticketProjects.data"
                    :key="project.id"
                    class="py-3"
                >
                    <Link
                        :href="base + '/projects/' + project.id + '/tickets'"
                        class="flex items-center justify-between gap-3 hover:text-primary"
                        ><span class="font-medium">{{ project.name }}</span
                        ><span
                            class="font-mono text-xs text-muted-foreground"
                            >{{ project.key }}</span
                        ></Link
                    >
                </li>
            </ul>
            <p
                v-if="!ticketProjects.data.length"
                class="text-sm text-muted-foreground"
            >
                No assigned projects.
            </p>
            <PaginationLinks :links="ticketProjects.links" />
        </section>
        <section v-if="members" class="overflow-hidden rounded-xl border">
            <div class="flex items-center justify-between gap-4 p-5">
                <h2 class="font-semibold">
                    Members
                    <span
                        class="ml-1 text-sm font-normal text-muted-foreground"
                        >{{ members.total }}</span
                    >
                </h2>
                <Button v-if="can['members.create']" @click="inviteOpen = true"
                    >Invite member</Button
                >
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-y bg-muted/40">
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Status</th>
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
                            <td>{{ member.role.name }}</td>
                            <td>{{ member.user.status }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        v-if="member.canUpdate"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            selectedMember = member;
                                            memberOpen = true;
                                        "
                                        >Change role</Button
                                    ><Button
                                        v-if="member.canRemove"
                                        variant="destructive"
                                        size="sm"
                                        @click="
                                            remove(
                                                base + '/members/' + member.id,
                                                'Remove member',
                                                'Remove ' +
                                                    member.user.name +
                                                    ' from this workspace? Their account will be preserved.',
                                                'Confirm removal',
                                            )
                                        "
                                        >Remove member</Button
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p
                v-if="!members.data.length"
                class="p-5 text-sm text-muted-foreground"
            >
                No members yet. Invite your first Manager.
            </p>
            <div class="px-5 pb-4">
                <PaginationLinks :links="members.links" />
            </div>
        </section>
        <div v-else-if="can['members.create']" class="flex justify-end">
            <Button @click="inviteOpen = true">Invite member</Button>
        </div>
        <section
            v-if="can['roles.view'] || can['roles.create']"
            class="rounded-xl border p-5"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Workspace roles</h2>
                <Button
                    v-if="can['roles.create']"
                    variant="outline"
                    @click="
                        selectedRole = undefined;
                        roleOpen = true;
                    "
                    >Create role</Button
                >
            </div>
            <ul class="divide-y">
                <li
                    v-for="role in roles"
                    :key="role.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-4"
                >
                    <div>
                        <p class="font-medium">
                            {{ role.name
                            }}<span
                                v-if="role.is_system"
                                class="ml-2 text-xs font-normal text-muted-foreground"
                                >Default</span
                            >
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="mr-2 font-mono">{{ role.code }}</span
                            >{{ role.permissions.length }} permission{{
                                role.permissions.length === 1 ? "" : "s"
                            }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="
                                selectedRole = role;
                                roleOpen = true;
                            "
                            >{{
                                role.canEdit ? "Edit role" : "View permissions"
                            }}</Button
                        ><Button
                            v-if="role.canDelete"
                            variant="destructive"
                            size="sm"
                            @click="
                                remove(
                                    base + '/roles/' + role.id,
                                    'Delete role',
                                    'Delete ' +
                                        role.name +
                                        '? Roles assigned to members or invitations cannot be deleted.',
                                    'Confirm deletion',
                                )
                            "
                            >Delete role</Button
                        >
                    </div>
                </li>
            </ul>
        </section>
        <section v-if="invitations" class="rounded-xl border p-5">
            <h2 class="mb-4 font-semibold">Pending invitations</h2>
            <ul class="divide-y">
                <li
                    v-for="invitation in invitations.data"
                    :key="invitation.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-4"
                >
                    <div>
                        <p class="break-all text-sm font-medium">
                            {{ invitation.email }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ invitation.role.name }} · Expires
                            {{
                                new Date(
                                    invitation.expires_at,
                                ).toLocaleDateString()
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="invitation.canCancel"
                        variant="outline"
                        size="sm"
                        @click="
                            remove(
                                base + '/invitations/' + invitation.id,
                                'Cancel invitation',
                                'Cancel invitation for ' +
                                    invitation.email +
                                    '?',
                                'Confirm cancellation',
                            )
                        "
                        >Cancel invitation</Button
                    >
                </li>
            </ul>
            <p
                v-if="!invitations.data.length"
                class="text-sm text-muted-foreground"
            >
                No pending invitations.
            </p>
            <PaginationLinks :links="invitations.links" />
        </section>
        <ActivityList v-if="activities" :activities="activities" />
        <p
            v-if="
                !members &&
                !(can['roles.view'] || can['roles.create']) &&
                !invitations &&
                !activities
            "
            class="rounded-xl border border-dashed p-8 text-sm text-muted-foreground"
        >
            You have access to this workspace. Contact your manager for
            membership changes.
        </p>
        <WorkspaceForm
            v-if="canEdit"
            v-model:open="workspaceOpen"
            :workspace="workspace"
        />
        <RoleForm
            v-if="can['roles.view'] || can['roles.create']"
            v-model:open="roleOpen"
            :workspace-id="workspace.id"
            :role="selectedRole"
            :modules="permissionModules"
            :can-edit-details="can['roles.edit']"
            :can-setup="can['roles.setup']"
            :read-only="selectedRole ? !selectedRole.canEdit : false"
        />
        <MemberForm
            v-if="can['members.edit']"
            v-model:open="memberOpen"
            :workspace-id="workspace.id"
            :member="selectedMember"
            :roles="roleOptions"
        />
        <InvitationForm
            v-if="can['members.create']"
            v-model:open="inviteOpen"
            :workspace-id="workspace.id"
            :roles="roleOptions"
        />
        <FormDialog
            v-model:open="confirmOpen"
            :title="action?.title ?? 'Confirm action'"
            :description="action?.description"
            ><form class="space-y-4" @submit.prevent="confirm">
                <FormErrors :errors="deletion.errors" /><Button
                    type="submit"
                    variant="destructive"
                    :disabled="deletion.processing"
                    >{{ action?.button }}</Button
                >
            </form></FormDialog
        >
    </AppLayout>
</template>
