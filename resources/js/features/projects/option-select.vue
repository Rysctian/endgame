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
import type { Option } from './types';
defineProps<{
    options: Option[];
    label: string;
    placeholder?: string;
    id?: string;
}>();
const model = defineModel<string>({ default: '' });
</script>
<template>
    <SelectRoot v-model="model">
        <SelectTrigger
            :id="id"
            :aria-label="label"
            class="flex h-10 w-full items-center justify-between rounded-md border bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
        >
            <SelectValue
                :placeholder="placeholder ?? 'Choose an option'"
            /><span aria-hidden="true">⌄</span>
        </SelectTrigger>
        <SelectPortal
            ><SelectContent
                position="popper"
                class="z-[60] max-h-72 min-w-[var(--reka-select-trigger-width)] overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
            >
                <SelectViewport
                    ><SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="String(option.value)"
                        class="flex cursor-default items-center justify-between gap-3 rounded-sm px-3 py-2 text-sm outline-none data-[highlighted]:bg-accent"
                    >
                        <SelectItemText>{{ option.label }}</SelectItemText
                        ><SelectItemIndicator>✓</SelectItemIndicator>
                    </SelectItem></SelectViewport
                >
            </SelectContent></SelectPortal
        >
    </SelectRoot>
</template>
