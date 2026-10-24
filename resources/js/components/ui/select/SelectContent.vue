<script setup lang="ts">
import type { SelectContentProps, SelectContentEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import {
    SelectContent,
    SelectPortal,
    SelectViewport,
    SelectScrollUpButton,
    SelectScrollDownButton,
    useForwardPropsEmits,
} from 'reka-ui';
import { ChevronUp, ChevronDown } from '@lucide/vue';
import { cn } from '@/lib/utils';
const props = withDefaults(
    defineProps<SelectContentProps & { class?: HTMLAttributes['class'] }>(),
    { position: 'popper', sideOffset: 4 },
);
const emits = defineEmits<SelectContentEmits>();
const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>
<template>
    <SelectPortal
        ><SelectContent
            data-slot="select-content"
            v-bind="forwarded"
            :class="
                cn(
                    'relative z-[80] max-h-72 min-w-[var(--reka-select-trigger-width)] overflow-hidden rounded-lg border bg-popover text-popover-foreground shadow-md',
                    props.class,
                )
            "
            ><SelectScrollUpButton class="flex items-center justify-center py-1"
                ><ChevronUp class="size-3" /></SelectScrollUpButton
            ><SelectViewport class="p-1"><slot /></SelectViewport
            ><SelectScrollDownButton
                class="flex items-center justify-center py-1"
                ><ChevronDown
                    class="size-3" /></SelectScrollDownButton></SelectContent
    ></SelectPortal>
</template>
