<script setup lang="ts">
import {
    DialogRoot,
    DialogPortal,
    DialogOverlay,
    DialogContent,
    DialogTitle,
    DialogDescription,
    DialogClose,
} from 'reka-ui';
import { Button } from '@/components/ui/button';
defineProps<{ title: string; description?: string; wide?: boolean }>();
const open = defineModel<boolean>('open', { default: false });
</script>
<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-black/40" />
            <DialogContent
                :class="wide ? 'max-w-3xl' : 'max-w-lg'"
                class="fixed left-1/2 top-1/2 z-50 max-h-[85dvh] w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border bg-background p-6 shadow-lg"
            >
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <DialogTitle class="text-lg font-semibold">{{
                            title
                        }}</DialogTitle
                        ><DialogDescription
                            :class="
                                description
                                    ? 'mt-1 text-sm text-muted-foreground'
                                    : 'sr-only'
                            "
                            >{{
                                description || 'Complete the fields below.'
                            }}</DialogDescription
                        >
                    </div>
                    <DialogClose as-child
                        ><Button variant="ghost" aria-label="Close dialog"
                            >×</Button
                        ></DialogClose
                    >
                </div>
                <slot />
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
