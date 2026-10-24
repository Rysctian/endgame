<script setup lang="ts">
import type { PopoverContentProps, PopoverContentEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { PopoverContent, PopoverPortal, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';
const props = withDefaults(
    defineProps<PopoverContentProps & { class?: HTMLAttributes['class'] }>(),
    { align: 'center', sideOffset: 4 },
);
const emits = defineEmits<PopoverContentEmits>();
const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>
<template>
    <PopoverPortal
        ><PopoverContent
            data-slot="popover-content"
            v-bind="forwarded"
            :class="
                cn(
                    'z-[80] w-72 rounded-xl border bg-popover p-4 text-popover-foreground shadow-md outline-none [transform-origin:var(--reka-popover-content-transform-origin)]',
                    props.class,
                )
            "
            ><slot /></PopoverContent
    ></PopoverPortal>
</template>
