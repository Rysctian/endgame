<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
const props = defineProps<{ token: string; email: string }>();
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <AuthLayout title="Choose a new password"
        ><form
            class="space-y-4"
            @submit.prevent="
                form.post('/reset-password', {
                    onFinish: () =>
                        form.reset('password', 'password_confirmation'),
                })
            "
        >
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="email">Email</label
                ><input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                />
            </div>
            <div class="space-y-2">
                <label for="password">Password</label
                ><input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    minlength="12"
                    required
                />
            </div>
            <div class="space-y-2">
                <label for="confirmation">Confirm password</label
                ><input
                    id="confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </div>
            <Button type="submit" :disabled="form.processing"
                >Reset password</Button
            >
        </form></AuthLayout
    >
</template>
