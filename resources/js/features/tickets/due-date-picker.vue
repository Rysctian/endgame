<script setup lang="ts">
import { computed, ref } from 'vue';
import { parseDate, type DateValue } from '@internationalized/date';
import { CalendarDays } from '@lucide/vue';
import { format, parseISO } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverTrigger,
    PopoverContent,
} from '@/components/ui/popover';
defineProps<{ label: string; disabled?: boolean }>();
const model = defineModel<string | null>({ default: null });
const open = ref(false);
const value = computed(() =>
    model.value ? parseDate(model.value) : undefined,
);
function select(value: DateValue | undefined) {
    model.value = value?.toString() ?? null;
    open.value = false;
}
</script>
<template>
    <Popover v-model:open="open"
        ><PopoverTrigger as-child
            ><Button
                type="button"
                variant="outline"
                class="ticket-control justify-start font-normal"
                :disabled="disabled"
                :aria-label="label"
                ><CalendarDays class="size-3.5" />{{
                    model ? format(parseISO(model), 'MMM d, yyyy') : label
                }}</Button
            ></PopoverTrigger
        ><PopoverContent align="start" class="w-auto p-0"
            ><Calendar :model-value="value" @update:model-value="select" />
            <div class="mx-3 flex justify-between border-t py-2">
                <Button
                    size="sm"
                    variant="ghost"
                    @click="
                        model = format(new Date(), 'yyyy-MM-dd');
                        open = false;
                    "
                    >Today</Button
                ><Button
                    size="sm"
                    variant="ghost"
                    @click="
                        model = null;
                        open = false;
                    "
                    >Clear date</Button
                >
            </div></PopoverContent
        ></Popover
    >
</template>
