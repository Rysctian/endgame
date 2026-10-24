<script setup lang="ts">
import type { SelectTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { SelectTrigger, SelectIcon, useForwardProps } from 'reka-ui';
import { ChevronDown } from '@lucide/vue';
import { cn } from '@/lib/utils';
const props = defineProps<
    SelectTriggerProps & { class?: HTMLAttributes['class'] }
>();
const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardProps(delegated);
</script>
<template>
    <SelectTrigger
        data-slot="select-trigger"
        v-bind="forwarded"
        :class="
            cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-input bg-transparent px-3 py-2 text-xs shadow-xs outline-none transition-[color,box-shadow] data-[placeholder]:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/20 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
        ><slot /><SelectIcon as-child
            ><ChevronDown class="size-3.5 shrink-0 opacity-50" /></SelectIcon
    ></SelectTrigger>
</template>
