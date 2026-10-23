<script setup lang="ts">
import PaginationLinks from './pagination-links.vue';
import type { Activity, Paginated } from './types';
defineProps<{ activities: Paginated<Activity> }>();
</script>
<template>
    <section class="rounded-xl border p-5">
        <h2 class="mb-4 font-semibold">Activity</h2>
        <ul class="divide-y">
            <li
                v-for="activity in activities.data"
                :key="activity.id"
                class="flex flex-wrap justify-between gap-2 py-3 text-sm"
            >
                <span>{{
                    activity.action.replaceAll('.', ' ').replaceAll('_', ' ')
                }}</span
                ><time
                    :datetime="activity.created_at"
                    class="text-muted-foreground"
                    >{{ new Date(activity.created_at).toLocaleString() }}</time
                >
            </li>
        </ul>
        <p v-if="!activities.data.length" class="text-sm text-muted-foreground">
            No activity yet.
        </p>
        <PaginationLinks :links="activities.links" />
    </section>
</template>
