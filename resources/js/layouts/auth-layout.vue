<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/features/user-access/types';
defineProps<{ title: string }>();
const page = usePage<SharedProps>();
</script>
<template>
    <Head :title="title" />
    <main class="flex min-h-dvh items-center justify-center bg-muted/30 p-5">
        <div
            class="w-full max-w-md space-y-6 rounded-xl border bg-background p-7 shadow-sm"
        >
            <Link href="/" class="text-sm font-semibold tracking-wide"
                >ENDGAME</Link
            >
            <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
            <p
                v-if="page.props.status"
                role="status"
                class="rounded-md bg-muted p-3 text-sm"
            >
                {{ page.props.status }}
            </p>
            <slot />
        </div>
    </main>
</template>
