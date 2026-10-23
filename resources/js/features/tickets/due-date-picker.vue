<script setup lang="ts">
import { computed, ref } from 'vue';
import { parseDate, type DateValue } from '@internationalized/date';
import {
    CalendarRoot,
    CalendarHeader,
    CalendarHeading,
    CalendarPrev,
    CalendarNext,
    CalendarGrid,
    CalendarGridHead,
    CalendarGridBody,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarCell,
    CalendarCellTrigger,
    PopoverRoot,
    PopoverTrigger,
    PopoverPortal,
    PopoverContent,
} from 'reka-ui';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import { format, parseISO } from 'date-fns';
import { Button } from '@/components/ui/button';
const props = defineProps<{ label: string; disabled?: boolean }>();
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
    <PopoverRoot v-model:open="open"
        ><PopoverTrigger as-child
            ><button
                type="button"
                class="ticket-control gap-2"
                :disabled="disabled"
                :aria-label="label"
            >
                <CalendarDays class="size-3.5" />{{
                    model ? format(parseISO(model), 'MMM d, yyyy') : label
                }}
            </button></PopoverTrigger
        ><PopoverPortal
            ><PopoverContent
                align="start"
                :side-offset="6"
                class="z-[80] rounded-lg border bg-popover p-3 shadow-md"
                ><CalendarRoot
                    v-slot="{ grid, weekDays }"
                    :model-value="value"
                    @update:model-value="select"
                    class="w-64"
                    ><CalendarHeader
                        class="mb-3 flex items-center justify-between"
                        ><CalendarPrev
                            class="ticket-icon"
                            aria-label="Previous month"
                            ><ChevronLeft class="size-4" /></CalendarPrev
                        ><CalendarHeading
                            class="text-sm font-medium" /><CalendarNext
                            class="ticket-icon"
                            aria-label="Next month"
                            ><ChevronRight
                                class="size-4" /></CalendarNext></CalendarHeader
                    ><CalendarGrid
                        v-for="month in grid"
                        :key="month.value.toString()"
                        class="w-full"
                        ><CalendarGridHead
                            ><CalendarGridRow
                                ><CalendarHeadCell
                                    v-for="day in weekDays"
                                    :key="day"
                                    class="pb-2 text-xs font-normal text-muted-foreground"
                                    >{{ day }}</CalendarHeadCell
                                ></CalendarGridRow
                            ></CalendarGridHead
                        ><CalendarGridBody
                            ><CalendarGridRow
                                v-for="(week, i) in month.rows"
                                :key="i"
                                ><CalendarCell
                                    v-for="day in week"
                                    :key="day.toString()"
                                    :date="day"
                                    ><CalendarCellTrigger
                                        :day="day"
                                        :month="month.value"
                                        class="flex size-9 items-center justify-center rounded-md text-sm hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[outside-view]:text-muted-foreground data-[today]:border" /></CalendarCell></CalendarGridRow></CalendarGridBody></CalendarGrid
                ></CalendarRoot>
                <div class="mt-2 flex justify-between border-t pt-2">
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
            ></PopoverPortal
        ></PopoverRoot
    >
</template>
