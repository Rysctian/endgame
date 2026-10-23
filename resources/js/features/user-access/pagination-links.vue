<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PageLink } from './types';
defineProps<{ links: PageLink[] }>();
function label(value: string) {
    return value
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/<[^>]*>/g, '');
}
</script>
<template>
    <nav
        v-if="links.length > 3"
        aria-label="Pagination"
        class="mt-4 flex flex-wrap gap-1"
    >
        <template v-for="(link, index) in links" :key="index"
            ><Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                :aria-current="link.active ? 'page' : undefined"
                class="rounded-md border px-3 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted font-semibold': link.active }"
                >{{ label(link.label) }}</Link
            ><span v-else class="px-3 py-1 text-sm text-muted-foreground">{{
                label(link.label)
            }}</span></template
        >
    </nav>
</template>
