<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Plus, ArrowUp, ArrowDown, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import TicketLayout from '@/layouts/ticket-layout.vue';
import TicketSelect from '@/features/tickets/ticket-select.vue';
import type { Project, Workspace, Option } from '@/features/tickets/types';
interface WorkflowStatus {
    code: string;
    name: string;
    category: string;
    is_initial: boolean;
    id?: number;
}
const props = defineProps<{
    workspace: Workspace;
    project: Project;
    statuses: WorkflowStatus[];
    transitions: { from: string; to: string }[];
    categories: Option[];
    canView: boolean;
}>();
const form = useForm({
    version: props.project.workflow_version,
    statuses: props.statuses.map(({ code, name, category, is_initial }) => ({
        code,
        name,
        category,
        is_initial,
    })),
    transitions: props.transitions.map((t) => ({ ...t })),
});
const base = computed(
    () => '/workspaces/' + props.workspace.id + '/projects/' + props.project.id,
);
function add() {
    let i = form.statuses.length + 1;
    while (form.statuses.some((s) => s.code === 'status_' + i)) i++;
    form.statuses.push({
        code: 'status_' + i,
        name: 'New status',
        category: String(props.categories[0].value),
        is_initial: false,
    });
}
function remove(index: number) {
    const [status] = form.statuses.splice(index, 1);
    form.transitions = form.transitions.filter(
        (t) => t.from !== status.code && t.to !== status.code,
    );
}
function reorder(index: number, offset: number) {
    const [status] = form.statuses.splice(index, 1);
    form.statuses.splice(index + offset, 0, status);
}
function initial(index: number) {
    form.statuses.forEach((s, i) => (s.is_initial = i === index));
}
function toggle(from: string, to: string, checked: boolean) {
    form.transitions = form.transitions.filter(
        (t) => !(t.from === from && t.to === to),
    );
    if (checked) form.transitions.push({ from, to });
}
function submit() {
    form.put(base.value + '/workflow', {
        preserveScroll: true,
        onSuccess: () => {
            form.version = props.project.workflow_version;
            toast.success('Workflow saved.');
        },
        onError: () =>
            toast.error(
                'Unable to save workflow. Check the highlighted fields.',
            ),
    });
}
</script>
<template>
    <TicketLayout
        :workspace="workspace"
        :project="project"
        active="workflow"
        :can-setup="true"
        :can-view="canView"
        ><form
            class="mx-auto max-w-5xl space-y-7 p-4 sm:p-6"
            @submit.prevent="submit"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold">Workflow</h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Arrange statuses and choose how tickets move between
                        them.
                    </p>
                </div>
                <Button type="submit" :disabled="form.processing">{{
                    form.processing ? 'Saving…' : 'Save workflow'
                }}</Button>
            </div>
            <section class="rounded-lg border">
                <div
                    class="flex items-center justify-between border-b px-4 py-3"
                >
                    <h3 class="text-sm font-medium">Statuses</h3>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="
                            form.processing || form.statuses.length >= 20
                        "
                        @click="add"
                        ><Plus class="size-3.5" />Add status</Button
                    >
                </div>
                <div class="overflow-x-auto p-4">
                    <div class="min-w-[680px]">
                        <div
                            class="grid grid-cols-[24px_1fr_1fr_130px_60px_85px] gap-3 pb-2 text-xs text-muted-foreground"
                        >
                            <span /><span>Name</span><span>Stable code</span
                            ><span>Category</span><span>Initial</span
                            ><span class="sr-only">Actions</span>
                        </div>
                        <div
                            v-for="(status, index) in form.statuses"
                            :key="index"
                            class="grid grid-cols-[24px_1fr_1fr_130px_60px_85px] items-start gap-3 border-t py-3"
                        >
                            <span class="pt-2 text-xs text-muted-foreground">{{
                                index + 1
                            }}</span>
                            <div>
                                <input
                                    v-model="status.name"
                                    :aria-label="'Status name ' + (index + 1)"
                                    class="ticket-input"
                                    maxlength="80"
                                    required
                                />
                                <p
                                    v-if="
                                        form.errors[
                                            ('statuses.' +
                                                index +
                                                '.name') as keyof typeof form.errors
                                        ]
                                    "
                                    class="ticket-error"
                                >
                                    {{
                                        form.errors[
                                            ('statuses.' +
                                                index +
                                                '.name') as keyof typeof form.errors
                                        ]
                                    }}
                                </p>
                            </div>
                            <div>
                                <input
                                    v-model="status.code"
                                    :aria-label="'Status code ' + (index + 1)"
                                    class="ticket-input font-mono text-xs"
                                    :readonly="
                                        props.statuses.some(
                                            (s) => s.code === status.code,
                                        )
                                    "
                                    maxlength="40"
                                    required
                                />
                                <p
                                    v-if="
                                        form.errors[
                                            ('statuses.' +
                                                index +
                                                '.code') as keyof typeof form.errors
                                        ]
                                    "
                                    class="ticket-error"
                                >
                                    {{
                                        form.errors[
                                            ('statuses.' +
                                                index +
                                                '.code') as keyof typeof form.errors
                                        ]
                                    }}
                                </p>
                            </div>
                            <TicketSelect
                                v-model="status.category"
                                :options="categories"
                                :label="'Category ' + (index + 1)"
                            />
                            <div class="pt-2">
                                <input
                                    type="radio"
                                    name="initial-status"
                                    :checked="status.is_initial"
                                    :aria-label="
                                        'Initial status ' + (index + 1)
                                    "
                                    class="accent-primary"
                                    @change="initial(index)"
                                />
                            </div>
                            <div class="flex">
                                <button
                                    type="button"
                                    class="ticket-icon"
                                    :disabled="index === 0 || form.processing"
                                    :aria-label="
                                        'Move status ' + (index + 1) + ' up'
                                    "
                                    @click="reorder(index, -1)"
                                >
                                    <ArrowUp class="size-3.5" /></button
                                ><button
                                    type="button"
                                    class="ticket-icon"
                                    :disabled="
                                        index === form.statuses.length - 1 ||
                                        form.processing
                                    "
                                    :aria-label="
                                        'Move status ' + (index + 1) + ' down'
                                    "
                                    @click="reorder(index, 1)"
                                >
                                    <ArrowDown class="size-3.5" /></button
                                ><button
                                    type="button"
                                    class="ticket-icon text-destructive"
                                    :disabled="
                                        form.statuses.length === 1 ||
                                        form.processing
                                    "
                                    :aria-label="'Remove status ' + (index + 1)"
                                    @click="remove(index)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.statuses" class="ticket-error px-4 pb-4">
                    {{ form.errors.statuses }}
                </p>
            </section>
            <section class="overflow-hidden rounded-lg border">
                <div class="border-b px-4 py-3">
                    <h3 class="text-sm font-medium">Allowed transitions</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Rows are the current status; columns are the
                        destination.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr>
                                <th
                                    class="min-w-36 border-b p-3 text-left font-medium"
                                >
                                    From → To
                                </th>
                                <th
                                    v-for="(status, i) in form.statuses"
                                    :key="i"
                                    class="min-w-28 border-b p-3 font-medium"
                                >
                                    {{ status.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(from, i) in form.statuses"
                                :key="i"
                                class="border-b last:border-0"
                            >
                                <th class="p-3 text-left font-medium">
                                    {{ from.name }}
                                </th>
                                <td
                                    v-for="(to, j) in form.statuses"
                                    :key="j"
                                    class="p-3 text-center"
                                >
                                    <span
                                        v-if="i === j"
                                        class="text-muted-foreground"
                                        >—</span
                                    ><input
                                        v-else
                                        type="checkbox"
                                        :aria-label="
                                            from.name + ' to ' + to.name
                                        "
                                        class="size-3.5 accent-primary"
                                        :checked="
                                            form.transitions.some(
                                                (t) =>
                                                    t.from === from.code &&
                                                    t.to === to.code,
                                            )
                                        "
                                        :disabled="form.processing"
                                        @change="
                                            toggle(
                                                from.code,
                                                to.code,
                                                (
                                                    $event.target as HTMLInputElement
                                                ).checked,
                                            )
                                        "
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="form.errors.transitions" class="ticket-error p-4">
                    {{ form.errors.transitions }}
                </p>
            </section>
            <p v-if="form.errors.version" class="ticket-error">
                {{ form.errors.version }}
            </p>
        </form></TicketLayout
    >
</template>
