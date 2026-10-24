<script setup lang="ts">
import { computed } from 'vue';
import { Activity as ActivityIcon } from '@lucide/vue';
import { format, isToday, isYesterday, parseISO } from 'date-fns';
import PaginationLinks from './pagination-links.vue';
import type { Activity, Paginated } from './types';
const props = defineProps<{ activities: Paginated<Activity> }>();
const groups = computed(() => {
    const map = new Map<string, Activity[]>();
    for (const activity of props.activities.data) {
        const date = parseISO(activity.created_at);
        const key = isToday(date)
            ? 'Today'
            : isYesterday(date)
              ? 'Yesterday'
              : format(date, 'MMM d, yyyy');
        map.set(key, [...(map.get(key) ?? []), activity]);
    }
    return [...map.entries()];
});
function label(action: string) {
    const text = action.replaceAll('.', ' ').replaceAll('_', ' ');
    return text.charAt(0).toUpperCase() + text.slice(1);
}
</script>
<template>
    <section id="activity" class="surface">
        <div class="section-heading">
            <h2 class="section-title">Activity</h2>
            <ActivityIcon
                class="size-4 text-muted-foreground"
                :stroke-width="1.5"
            />
        </div>
        <div class="px-5 py-4">
            <div
                v-for="[date, items] in groups"
                :key="date"
                class="mb-5 last:mb-0"
            >
                <h3
                    class="mb-4 text-[10px] font-medium uppercase tracking-[0.08em] text-muted-foreground"
                >
                    {{ date }}
                </h3>
                <ol class="ml-3 border-l">
                    <li
                        v-for="activity in items"
                        :key="activity.id"
                        class="relative flex flex-wrap items-start justify-between gap-x-5 gap-y-1 pb-5 pl-6 last:pb-1"
                    >
                        <span
                            class="absolute -left-3.5 top-0 flex size-7 items-center justify-center rounded-full border-4 border-card bg-muted text-muted-foreground"
                            ><ActivityIcon class="size-3" /></span
                        ><span class="text-xs leading-6">{{
                            label(activity.action)
                        }}</span
                        ><time
                            :datetime="activity.created_at"
                            class="text-[10px] leading-6 text-muted-foreground"
                            >{{
                                format(parseISO(activity.created_at), 'HH:mm')
                            }}</time
                        >
                    </li>
                </ol>
            </div>
            <p
                v-if="!activities.data.length"
                class="py-5 text-center text-xs text-muted-foreground"
            >
                No activity yet.
            </p>
            <PaginationLinks :links="activities.links" />
        </div>
    </section>
</template>
