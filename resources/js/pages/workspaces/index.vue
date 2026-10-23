<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout.vue';
import WorkspaceForm from '@/features/user-access/workspace-form.vue';
import PaginationLinks from '@/features/user-access/pagination-links.vue';
import type { Workspace, Paginated } from '@/features/user-access/types';
defineProps<{ workspaces: Paginated<Workspace>; canCreate: boolean }>();
const open = ref(false);
</script>
<template>
    <AppLayout title="Workspaces"
        ><div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight">Workspaces</h1>
            <Button v-if="canCreate" @click="open = true"
                >Create workspace</Button
            >
        </div>
        <div
            v-if="workspaces.data.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="workspace in workspaces.data"
                :key="workspace.id"
                :href="'/workspaces/' + workspace.id"
                class="min-w-0 rounded-xl border p-6 transition-colors hover:bg-muted/50"
                ><h2 class="break-words font-semibold">{{ workspace.name }}</h2>
                <p class="mt-2 truncate text-sm text-muted-foreground">
                    {{ workspace.slug }}
                </p></Link
            >
        </div>
        <div v-else class="rounded-xl border border-dashed p-12 text-center">
            <h2 class="font-medium">No workspaces yet</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                {{
                    canCreate
                        ? 'Create a workspace to get started.'
                        : 'Accept an invitation from your workspace manager to get started.'
                }}
            </p>
        </div>
        <PaginationLinks :links="workspaces.links" /><WorkspaceForm
            v-if="canCreate"
            v-model:open="open"
    /></AppLayout>
</template>
