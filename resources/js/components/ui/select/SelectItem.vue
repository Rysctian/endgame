<script setup lang="ts">
import type { SelectItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import {
    SelectItem,
    SelectItemText,
    SelectItemIndicator,
    useForwardProps,
} from 'reka-ui';
import { Check } from '@lucide/vue';
import { cn } from '@/lib/utils';
const props = defineProps<
    SelectItemProps & { class?: HTMLAttributes['class'] }
>();
const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardProps(delegated);
</script>
<template>
    <SelectItem
        data-slot="select-item"
        v-bind="forwarded"
        :class="
            cn(
                'relative flex w-full cursor-default items-center gap-2 rounded-md py-2 pl-2.5 pr-8 text-xs outline-none focus:bg-accent focus:text-accent-foreground data-[highlighted]:bg-accent data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                props.class,
            )
        "
        ><SelectItemText><slot /></SelectItemText
        ><span class="absolute right-2 flex size-4 items-center justify-center"
            ><SelectItemIndicator
                ><Check class="size-3.5" /></SelectItemIndicator></span
    ></SelectItem>
</template>
