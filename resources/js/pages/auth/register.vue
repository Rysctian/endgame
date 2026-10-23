<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
<template>
    <AuthLayout title="Create account"
        ><form class="space-y-4" @submit.prevent="submit">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="name">Name</label
                ><input
                    id="name"
                    v-model="form.name"
                    autocomplete="name"
                    required
                    maxlength="255"
                />
            </div>
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
                <p class="text-xs text-muted-foreground">
                    At least 12 characters.
                </p>
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
            <Button type="submit" class="w-full" :disabled="form.processing"
                >Create account</Button
            >
        </form>
        <p class="text-sm">
            <Link href="/login" class="underline"
                >Already have an account? Sign in</Link
            >
        </p></AuthLayout
    >
</template>
