<script setup lang="ts">
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
        ><h1 class="text-2xl font-semibold">Profile</h1>
        <div class="grid gap-8 md:grid-cols-2">
            <section class="rounded-xl border p-6">
                <h2 class="mb-5 font-semibold">Account details</h2>
                <form
                    class="space-y-4"
                    @submit.prevent="profile.patch('/profile')"
                >
                    <FormErrors :errors="profile.errors" />
                    <div class="space-y-2">
                        <label for="name">Name</label
                        ><input
                            id="name"
                            v-model="profile.name"
                            autocomplete="name"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label for="email">Email</label
                        ><input
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
            <section class="rounded-xl border p-6">
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
                        ><input
                            id="current"
                            v-model="password.current_password"
                            type="password"
                            autocomplete="current-password"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label for="new">New password</label
                        ><input
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
                        ><input
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
