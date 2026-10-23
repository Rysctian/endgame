<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from './form-dialog.vue';
import FormErrors from './form-errors.vue';
import PermissionMatrix from './permission-matrix.vue';
import type { Role, PermissionModule } from './types';
const props = defineProps<{
    workspaceId: number;
    role?: Role;
    modules: PermissionModule[];
    canEditDetails: boolean;
    canSetup: boolean;
    readOnly?: boolean;
}>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ name: '', code: '', permissions: [] as string[] });
const title = computed(() =>
    props.readOnly
        ? 'Role permissions'
        : props.role
          ? 'Edit role'
          : 'Create role',
);
watch(open, (value) => {
    if (value) {
        form.name = props.role?.name ?? '';
        form.code = props.role?.code ?? '';
        form.permissions = [...(props.role?.permissions ?? [])];
        form.clearErrors();
    }
});
function submit() {
    if (props.readOnly) return;
    const base = '/workspaces/' + props.workspaceId + '/roles';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    if (props.role) form.patch(base + '/' + props.role.id, options);
    else form.post(base, options);
}
</script>
<template>
    <FormDialog v-model:open="open" :title="title" wide>
        <form class="space-y-5" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label for="role-name">Role name</label
                    ><input
                        id="role-name"
                        v-model="form.name"
                        :readonly="readOnly || (role && !canEditDetails)"
                        required
                        maxlength="100"
                    />
                </div>
                <div class="space-y-2">
                    <label for="role-code">Role code</label
                    ><input
                        id="role-code"
                        v-model="form.code"
                        :readonly="readOnly || (role && !canEditDetails)"
                        required
                        maxlength="100"
                        pattern="[a-z][a-z0-9]*(-[a-z0-9]+)*"
                    />
                    <p class="text-xs text-muted-foreground">
                        A unique code within this workspace.
                    </p>
                </div>
            </div>
            <fieldset class="space-y-3">
                <legend class="mb-3 text-sm font-medium">
                    Module permissions
                </legend>
                <PermissionMatrix
                    v-model="form.permissions"
                    :modules="modules"
                    :editable="!readOnly && canSetup"
                />
            </fieldset>
            <Button v-if="!readOnly" type="submit" :disabled="form.processing"
                >Save role</Button
            >
        </form>
    </FormDialog>
</template>
