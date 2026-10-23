<script setup lang="ts">
import {
    SelectRoot,
    SelectTrigger,
    SelectValue,
    SelectPortal,
    SelectContent,
    SelectViewport,
    SelectItem,
    SelectItemText,
    SelectItemIndicator,
} from 'reka-ui';
import { ChevronDown, Check } from '@lucide/vue';
import type { Option } from './types';
defineProps<{
    options: Option[];
    label: string;
    placeholder?: string;
    disabled?: boolean;
    id?: string;
}>();
const model = defineModel<string>({ default: '' });
</script>
<template>
    <SelectRoot v-model="model" :disabled="disabled"
        ><SelectTrigger
            :id="id"
            :aria-label="label"
            class="ticket-control justify-between gap-2"
            ><SelectValue :placeholder="placeholder ?? label" /><ChevronDown
                class="size-3 shrink-0 text-muted-foreground"
        /></SelectTrigger>
        <SelectPortal
            ><SelectContent
                position="popper"
                :side-offset="4"
                class="z-[80] max-h-64 min-w-[var(--reka-select-trigger-width)] overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
                ><SelectViewport
                    ><SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="String(option.value)"
                        class="flex cursor-default items-center justify-between gap-5 rounded px-2.5 py-2 text-sm outline-none data-[highlighted]:bg-accent"
                        ><SelectItemText>{{ option.label }}</SelectItemText
                        ><SelectItemIndicator
                            ><Check
                                class="size-3.5" /></SelectItemIndicator></SelectItem></SelectViewport></SelectContent
        ></SelectPortal>
    </SelectRoot>
</template>
