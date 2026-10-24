<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        variant?: 'default' | 'secondary' | 'outline' | 'destructive';
    }>(),
    { variant: 'default' },
);
</script>
<template>
    <span
        data-slot="badge"
        :class="
            cn(
                'inline-flex w-fit shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-md border px-2 py-0.5 text-[10px] font-medium',
                variant === 'default'
                    ? 'border-transparent bg-primary text-primary-foreground'
                    : variant === 'secondary'
                      ? 'border-transparent bg-secondary text-secondary-foreground'
                      : variant === 'destructive'
                        ? 'border-destructive/15 bg-destructive/10 text-destructive'
                        : 'border-border bg-transparent text-foreground',
                props.class,
            )
        "
        ><slot
    /></span>
</template>
