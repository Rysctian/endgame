<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import {
    DialogContent,
    DialogPortal,
    DialogOverlay,
    DialogClose,
    useForwardPropsEmits,
} from 'reka-ui';
import { X } from '@lucide/vue';
import { cn } from '@/lib/utils';
const props = withDefaults(
    defineProps<
        DialogContentProps & {
            class?: HTMLAttributes['class'];
            side?: 'left' | 'right';
            showCloseButton?: boolean;
        }
    >(),
    { side: 'right', showCloseButton: true },
);
const emits = defineEmits<DialogContentEmits>();
const delegated = computed(() => {
    const { class: _, side: __, showCloseButton: ___, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>
<template>
    <DialogPortal
        ><DialogOverlay
            data-slot="sheet-overlay"
            class="app-overlay z-50" /><DialogContent
            data-slot="sheet-content"
            v-bind="forwarded"
            :class="
                cn(
                    'fixed inset-y-0 z-[60] flex h-dvh w-[min(560px,100vw)] flex-col gap-4 bg-background shadow-xl outline-none',
                    side === 'right' ? 'right-0 border-l' : 'left-0 border-r',
                    props.class,
                )
            "
            ><slot /><DialogClose
                v-if="showCloseButton"
                class="icon-control absolute right-4 top-4"
                aria-label="Close panel"
                ><X class="size-4" /></DialogClose></DialogContent
    ></DialogPortal>
</template>
