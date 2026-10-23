<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from './form-dialog.vue';
import FormErrors from './form-errors.vue';
import RoleSelect from './role-select.vue';
import type { Member, RoleOption } from './types';
const props = defineProps<{
    workspaceId: number;
    member?: Member;
    roles: RoleOption[];
}>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ role_id: '' });
watch(open, (value) => {
    if (value) {
        form.role_id = String(props.member?.role.id ?? '');
        form.clearErrors();
    }
});
function submit() {
    if (props.member)
        form.patch(
            '/workspaces/' + props.workspaceId + '/members/' + props.member.id,
            {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
            },
        );
}
</script>
<template>
    <FormDialog
        v-model:open="open"
        title="Change member role"
        :description="member?.user.email"
        ><form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="member-role">Workspace role</label
                ><RoleSelect
                    id="member-role"
                    v-model="form.role_id"
                    :options="roles"
                />
            </div>
            <Button type="submit" :disabled="form.processing || !form.role_id"
                >Save member</Button
            >
        </form></FormDialog
    >
</template>
