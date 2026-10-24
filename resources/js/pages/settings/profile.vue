<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app-layout.vue';
import FormErrors from '@/features/user-access/form-errors.vue';
import { Button } from '@/components/ui/button';
import type { SharedProps } from '@/features/user-access/types';
const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user!);
const profile = useForm({ name: user.value.name, email: user.value.email });
const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <AppLayout title="Profile"
        ><div class="flex items-center gap-4 border-b pb-6">
            <span class="avatar-token size-12 text-sm">{{
                user.name
                    .split(' ')
                    .map((n) => n[0])
                    .slice(0, 2)
                    .join('')
            }}</span>
            <div>
                <h1>Profile</h1>
                <p class="mt-1.5 text-xs text-muted-foreground">
                    Your account, your preferences.
                </p>
            </div>
        </div>
        <div class="grid max-w-5xl items-start gap-5 md:grid-cols-2">
            <section class="surface p-6">
                <h2 class="mb-5 font-semibold">Account details</h2>
                <form
                    class="space-y-4"
                    @submit.prevent="profile.patch('/profile')"
                >
                    <FormErrors :errors="profile.errors" />
                    <div class="space-y-2">
                        <label for="name">Name</label
                        ><Input
                            id="name"
                            v-model="profile.name"
                            autocomplete="name"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label for="email">Email</label
                        ><Input
                            id="email"
                            v-model="profile.email"
                            type="email"
                            autocomplete="email"
                            required
                        />
                    </div>
                    <p v-if="!user.email_verified_at" class="text-sm">
                        <Link href="/email/verify" class="underline"
                            >Verify your email</Link
                        >
                    </p>
                    <Button type="submit" :disabled="profile.processing"
                        >Save profile</Button
                    >
                </form>
            </section>
            <section class="surface p-6">
                <h2 class="mb-5 font-semibold">Change password</h2>
                <form
                    class="space-y-4"
                    @submit.prevent="
                        password.put('/profile/password', {
                            onSuccess: () => password.reset(),
                        })
                    "
                >
                    <FormErrors :errors="password.errors" />
                    <div class="space-y-2">
                        <label for="current">Current password</label
                        ><Input
                            id="current"
                            v-model="password.current_password"
                            type="password"
                            autocomplete="current-password"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label for="new">New password</label
                        ><Input
                            id="new"
                            v-model="password.password"
                            type="password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label for="confirmation">Confirm password</label
                        ><Input
                            id="confirmation"
                            v-model="password.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                    <Button type="submit" :disabled="password.processing"
                        >Update password</Button
                    >
                </form>
            </section>
        </div></AppLayout
    >
</template>
