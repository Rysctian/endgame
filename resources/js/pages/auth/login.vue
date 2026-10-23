<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
const form = useForm({ email: '', password: '', remember: false });
function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>
<template>
    <AuthLayout title="Sign in"
        ><form class="space-y-4" @submit.prevent="submit">
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
                    autocomplete="current-password"
                    required
                />
            </div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <label class="flex items-center gap-2"
                    ><input v-model="form.remember" type="checkbox" />Remember
                    me</label
                ><Link href="/forgot-password" class="underline"
                    >Forgot password?</Link
                >
            </div>
            <Button type="submit" class="w-full" :disabled="form.processing"
                >Sign in</Button
            >
        </form>
        <p class="text-sm text-muted-foreground">
            New to Endgame?
            <Link href="/register" class="text-foreground underline"
                >Create an account</Link
            >
        </p></AuthLayout
    >
</template>
