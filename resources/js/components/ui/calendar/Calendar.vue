<script setup lang="ts">
import type { CalendarRootProps, CalendarRootEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
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
    useForwardPropsEmits,
} from 'reka-ui';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { cn } from '@/lib/utils';
const props = defineProps<
    CalendarRootProps & { class?: HTMLAttributes['class'] }
>();
const emits = defineEmits<CalendarRootEmits>();
const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>
<template>
    <CalendarRoot
        v-slot="{ grid, weekDays }"
        data-slot="calendar"
        v-bind="forwarded"
        :class="cn('p-3', props.class)"
        ><CalendarHeader class="relative mb-4 flex items-center justify-between"
            ><CalendarPrev
                class="icon-control size-7 rounded-md border"
                aria-label="Previous month"
                ><ChevronLeft class="size-3.5" /></CalendarPrev
            ><CalendarHeading class="text-xs font-medium" /><CalendarNext
                class="icon-control size-7 rounded-md border"
                aria-label="Next month"
                ><ChevronRight class="size-3.5" /></CalendarNext
        ></CalendarHeader>
        <div class="flex flex-col gap-4 sm:flex-row">
            <CalendarGrid
                v-for="month in grid"
                :key="month.value.toString()"
                class="w-full border-collapse"
                ><CalendarGridHead
                    ><CalendarGridRow
                        ><CalendarHeadCell
                            v-for="day in weekDays"
                            :key="day"
                            class="w-9 pb-2 text-center text-[10px] font-normal text-muted-foreground"
                            >{{ day }}</CalendarHeadCell
                        ></CalendarGridRow
                    ></CalendarGridHead
                ><CalendarGridBody
                    ><CalendarGridRow
                        v-for="(week, index) in month.rows"
                        :key="index"
                        ><CalendarCell
                            v-for="day in week"
                            :key="day.toString()"
                            :date="day"
                            class="p-0 text-center"
                            ><CalendarCellTrigger
                                :day="day"
                                :month="month.value"
                                class="flex size-9 items-center justify-center rounded-md text-xs outline-none hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[outside-view]:text-muted-foreground/50 data-[today]:border data-[disabled]:pointer-events-none data-[disabled]:opacity-30" /></CalendarCell></CalendarGridRow></CalendarGridBody
            ></CalendarGrid></div
    ></CalendarRoot>
</template>
