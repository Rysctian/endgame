<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
    DialogClose,
} from '@/components/ui/dialog';
import { X } from '@lucide/vue';
defineProps<{ title: string; description?: string; wide?: boolean }>();
const open = defineModel<boolean>('open', { default: false });
const opener = ref<HTMLElement | null>(null);
watch(open, (value) => {
    if (value) opener.value = document.activeElement as HTMLElement | null;
});
function restoreFocus(event: Event) {
    event.preventDefault();
    nextTick(() => opener.value?.isConnected && opener.value.focus());
}
</script>
<template>
    <Dialog v-model:open="open"
        ><DialogContent
            :show-close-button="false"
            :class="wide ? 'sm:max-w-3xl' : 'sm:max-w-lg'"
            class="app-dialog"
            @close-auto-focus="restoreFocus"
            ><div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <DialogTitle
                        class="text-base font-semibold tracking-tight"
                        >{{ title }}</DialogTitle
                    ><DialogDescription
                        :class="
                            description
                                ? 'mt-1.5 text-xs leading-5 text-muted-foreground'
                                : 'sr-only'
                        "
                        >{{
                            description || 'Complete the fields below.'
                        }}</DialogDescription
                    >
                </div>
                <DialogClose
                    class="icon-control -mr-2 -mt-1"
                    aria-label="Close dialog"
                    ><X class="size-4"
                /></DialogClose>
            </div>
            <slot /></DialogContent
    ></Dialog>
</template>
