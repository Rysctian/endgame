<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
    DialogClose,
} from '@/components/ui/dialog';
import { X, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import TicketProperties from './ticket-properties.vue';
import type { Contract } from './types';
const props = defineProps<{
    contract: Contract;
    url: string;
    initialStatus: string;
}>();
const open = defineModel<boolean>('open', { default: false });
const form = useForm({ ...props.contract.defaults });
watch(open, (value) => {
    if (value) {
        form.defaults({ ...props.contract.defaults });
        form.reset();
        form.clearErrors();
    }
});
function restoreFocus(event: Event) {
    event.preventDefault();
    document.getElementById('new-ticket')?.focus();
}
function submit() {
    form.post(props.url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            toast.success('Ticket created.');
        },
        onError: () =>
            toast.error(
                'Unable to create ticket. Check the highlighted fields.',
            ),
    });
}
</script>
<template>
    <Dialog v-model:open="open"
        ><DialogContent
            :show-close-button="false"
            class="ticket-dialog"
            @close-auto-focus="restoreFocus"
            @interact-outside="form.processing && $event.preventDefault()"
            ><div class="mb-5 flex items-center justify-between">
                <DialogTitle class="text-base font-semibold"
                    >New ticket</DialogTitle
                ><DialogClose
                    class="ticket-icon"
                    aria-label="Close dialog"
                    :disabled="form.processing"
                    ><X class="size-4"
                /></DialogClose>
            </div>
            <DialogDescription class="sr-only"
                >Create a ticket in this project.</DialogDescription
            >
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label for="new-title" class="sr-only">{{
                            contract.fieldLabels.title
                        }}</label
                        ><Input
                            id="new-title"
                            v-model="form.title"
                            class="ticket-input h-11 text-base"
                            placeholder="Ticket title"
                            :required="contract.fieldStates.title.required"
                            :maxlength="
                                contract.validationRules.title.maxLength
                            "
                        />
                        <p v-if="form.errors.title" class="ticket-error">
                            {{ form.errors.title }}
                        </p>
                    </div>
                    <div>
                        <label for="new-description" class="sr-only">{{
                            contract.fieldLabels.description
                        }}</label
                        ><Textarea
                            id="new-description"
                            v-model="form.description"
                            class="ticket-input min-h-28 resize-y"
                            placeholder="Add a description…"
                            :maxlength="
                                contract.validationRules.description.maxLength
                            "
                        />
                        <p v-if="form.errors.description" class="ticket-error">
                            {{ form.errors.description }}
                        </p>
                    </div>
                    <TicketProperties
                        v-model="form"
                        :contract="contract"
                        :errors="form.errors"
                        :disabled="form.processing"
                    />
                    <p class="text-xs text-muted-foreground">
                        Initial status:
                        <span class="font-medium text-foreground">{{
                            initialStatus
                        }}</span>
                    </p>
                </div>
                <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                    <DialogClose as-child
                        ><Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            >Cancel</Button
                        ></DialogClose
                    ><Button type="submit" :disabled="form.processing"
                        ><Plus class="size-3.5" />{{
                            form.processing ? 'Creating…' : 'Create ticket'
                        }}</Button
                    >
                </div>
            </form></DialogContent
        ></Dialog
    >
</template>
