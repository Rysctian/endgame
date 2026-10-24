<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
const props = defineProps<{ class?: HTMLAttributes['class'] }>();
const model = defineModel<string | null>();
</script>
<template>
    <textarea
        v-model="model"
        data-slot="textarea"
        :class="
            cn(
                'flex min-h-20 w-full rounded-lg border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/20 aria-invalid:border-destructive aria-invalid:ring-destructive/20 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    />
</template>
