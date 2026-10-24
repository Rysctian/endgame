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
            showCloseButton?: boolean;
        }
    >(),
    { showCloseButton: true },
);
const emits = defineEmits<DialogContentEmits>();
const delegated = computed(() => {
    const { class: _, showCloseButton: __, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>
<template>
    <DialogPortal
        ><DialogOverlay
            data-slot="dialog-overlay"
            class="app-overlay z-50" /><DialogContent
            data-slot="dialog-content"
            v-bind="forwarded"
            :class="
                cn(
                    'app-dialog z-[60] w-full max-w-[calc(100%-2rem)] sm:max-w-lg',
                    props.class,
                )
            "
            ><slot /><DialogClose
                v-if="showCloseButton"
                class="icon-control absolute right-4 top-4"
                aria-label="Close dialog"
                ><X class="size-4" /></DialogClose></DialogContent
    ></DialogPortal>
</template>
