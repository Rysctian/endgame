<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
const form = useForm({ email: '' });
</script>
<template>
    <AuthLayout title="Reset your password"
        ><p class="text-sm text-muted-foreground">
            Enter your email to request a password reset link.
        </p>
        <form class="space-y-4" @submit.prevent="form.post('/forgot-password')">
            <FormErrors :errors="form.errors" />
            <div class="space-y-2">
                <label for="email">Email</label
                ><input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                />
            </div>
            <Button type="submit" class="w-full" :disabled="form.processing"
                >Send reset link</Button
            >
        </form>
        <Link href="/login" class="text-sm underline"
            >Back to sign in</Link
        ></AuthLayout
    >
</template>
