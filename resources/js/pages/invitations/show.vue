<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
const props = defineProps<{
    invitation: {
        id: number;
        email: string;
        workspace: { name: string };
        role: { name: string };
        accepted: boolean;
    };
    token: string;
}>();
const form = useForm({});
function accept() {
    form.post('/invitations/' + props.invitation.id + '/' + props.token);
}
</script>
<template>
    <AppLayout title="Workspace invitation"
        ><section class="mx-auto max-w-lg space-y-5 rounded-xl border p-7">
            <h1 class="text-2xl font-semibold">
                Join {{ invitation.workspace.name }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ invitation.email }} has been invited as
                {{ invitation.role.name }}.
            </p>
            <p v-if="invitation.accepted" class="text-sm">
                You already accepted this invitation.
            </p>
            <FormErrors :errors="form.errors" /><Button
                :disabled="form.processing"
                @click="accept"
                >{{
                    invitation.accepted
                        ? 'Continue to workspace'
                        : 'Accept invitation'
                }}</Button
            >
        </section></AppLayout
    >
</template>
