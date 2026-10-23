<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormDialog from './form-dialog.vue';
import FormErrors from './form-errors.vue';
import RoleSelect from './role-select.vue';
import type { RoleOption } from './types';
const props = defineProps<{ workspaceId: number; roles: RoleOption[] }>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ email: '', role_id: '' });
watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
    }
});
function submit() {
    form.post('/workspaces/' + props.workspaceId + '/invitations', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>
<template>
    <FormDialog v-model:open="open" title="Invite member"
        ><form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="invite-email">Invitation email</label
                ><input
                    id="invite-email"
                    v-model="form.email"
                    type="email"
                    required
                />
            </div>
            <div class="space-y-2">
                <label for="invite-role">Workspace role</label
                ><RoleSelect
                    id="invite-role"
                    v-model="form.role_id"
                    :options="roles"
                />
            </div>
            <Button type="submit" :disabled="form.processing || !form.role_id"
                >Send invitation</Button
            >
        </form></FormDialog
    >
</template>
