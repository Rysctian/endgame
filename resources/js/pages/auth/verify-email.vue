<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth-layout.vue';
import { Button } from '@/components/ui/button';
import FormErrors from '@/features/user-access/form-errors.vue';
const form = useForm({});
</script>
<template>
    <AuthLayout title="Verify your email"
        ><p class="text-sm text-muted-foreground">
            Open the verification link in your email to continue. After
            verifying, you can return to your invitation.
        </p>
        <FormErrors :errors="form.errors" /><Button
            class="w-full"
            :disabled="form.processing"
            @click="form.post('/email/verification-notification')"
            >Resend verification email</Button
        >
        <div class="flex items-center justify-between">
            <Link href="/profile" class="text-sm underline">Change email</Link
            ><Button variant="ghost" @click="router.post('/logout')"
                >Sign out</Button
            >
        </div></AuthLayout
    >
</template>
