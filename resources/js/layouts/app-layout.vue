<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FormErrors from '@/features/user-access/form-errors.vue';
import type { SharedProps } from '@/features/user-access/types';
defineProps<{ title: string }>();
const page = usePage<SharedProps>();
</script>
<template>
    <Head :title="title" />
    <div class="min-h-dvh bg-background text-foreground">
        <header class="border-b">
            <nav
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-5 py-4"
                aria-label="Main navigation"
            >
                <Link
                    href="/workspaces"
                    class="mr-auto font-semibold tracking-tight"
                    >ENDGAME</Link
                >
                <template v-if="page.props.auth.user">
                    <Link href="/workspaces" class="text-sm hover:underline"
                        >Workspaces</Link
                    >
                    <Link
                        v-if="page.props.auth.user.is_system_admin"
                        href="/admin/users"
                        class="text-sm hover:underline"
                        >Accounts</Link
                    >
                    <Link href="/profile" class="text-sm hover:underline"
                        >Profile</Link
                    >
                    <Button variant="ghost" @click="router.post('/logout')"
                        >Sign out</Button
                    >
                </template>
                <Link v-else href="/login" class="text-sm hover:underline"
                    >Sign in</Link
                >
            </nav>
        </header>
        <main class="mx-auto max-w-6xl space-y-6 px-5 py-8">
            <p
                v-if="page.props.status"
                role="status"
                class="rounded-lg border bg-muted px-4 py-3 text-sm"
            >
                {{ page.props.status }}
            </p>
            <FormErrors :errors="page.props.errors" />
            <slot />
        </main>
    </div>
</template>
