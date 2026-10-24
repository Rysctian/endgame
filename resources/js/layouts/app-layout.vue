<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppShell from './app-shell.vue';
import type { SharedProps } from '@/features/user-access/types';
defineProps<{ title: string }>();
const page = usePage<
    SharedProps & {
        workspace?: { id: number; name: string };
        can?: Record<string, boolean>;
    }
>();
const canProjects = computed(
    () =>
        page.component.startsWith('projects/') ||
        !!page.props.can?.['projects.view'] ||
        !!page.props.can?.['projects.create'],
);
</script>
<template>
    <AppShell
        :title="title"
        :workspace="page.props.workspace"
        :can-projects="canProjects"
        ><div
            class="page-content mx-auto max-w-[1440px] space-y-6 px-4 py-6 sm:px-7 sm:py-8"
        >
            <slot /></div
    ></AppShell>
</template>
