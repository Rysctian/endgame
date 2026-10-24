<script setup lang="ts">
import { Card } from '@/components/ui/card';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Plus, ArrowUpRight, Layers2, Building2 } from '@lucide/vue';
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
        ><div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="mb-2 text-[11px] font-medium text-muted-foreground">
                    Your home for good work
                </p>
                <h1>Workspaces</h1>
                <p class="mt-2 text-xs text-muted-foreground">
                    Choose where you want to focus.
                </p>
            </div>
            <Button v-if="canCreate" @click="open = true"
                ><Plus class="size-3.5" />Create workspace</Button
            >
        </div>
        <div
            v-if="workspaces.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="workspace in workspaces.data"
                :key="workspace.id"
                :href="'/workspaces/' + workspace.id"
                :aria-label="workspace.name"
                class="block h-full"
            >
                <Card class="project-card group h-full"
                    ><div class="mb-6 flex items-center justify-between">
                        <span
                            class="flex size-11 items-center justify-center rounded-xl border bg-muted text-sm font-semibold text-foreground"
                            >{{
                                workspace.name.slice(0, 2).toUpperCase()
                            }}</span
                        ><ArrowUpRight
                            class="size-4 text-muted-foreground/50 group-hover:text-primary"
                        />
                    </div>
                    <h2 class="break-words text-sm font-semibold">
                        {{ workspace.name }}
                    </h2>
                    <p class="mt-1.5 truncate text-xs text-muted-foreground">
                        {{ workspace.slug }}
                    </p>
                    <div
                        class="mt-6 flex items-center gap-1.5 border-t pt-3 text-[10px] text-muted-foreground"
                    >
                        <Layers2 class="size-3" />Open workspace
                    </div></Card
                ></Link
            >
        </div>
        <div v-else class="empty-state">
            <span class="empty-symbol"><Building2 class="size-5" /></span>
            <h2 class="text-sm font-medium">No workspaces yet</h2>
            <p class="mt-2 max-w-xs text-xs leading-5 text-muted-foreground">
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
