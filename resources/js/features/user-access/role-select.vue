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
import type { RoleOption } from './types';
defineProps<{ options: RoleOption[]; id?: string }>();
const model = defineModel<string>({ default: '' });
</script>
<template>
    <SelectRoot v-model="model">
        <SelectTrigger
            :id="id"
            aria-label="Workspace role"
            class="flex h-10 w-full items-center justify-between rounded-md border bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
            ><SelectValue placeholder="Choose a role" /><span aria-hidden="true"
                >⌄</span
            ></SelectTrigger
        >
        <SelectPortal
            ><SelectContent
                position="popper"
                class="z-[60] min-w-[var(--reka-select-trigger-width)] rounded-md border bg-popover p-1 shadow-md"
                ><SelectViewport>
                    <SelectItem
                        v-for="role in options"
                        :key="role.id"
                        :value="String(role.id)"
                        class="flex cursor-default items-center justify-between gap-3 rounded-sm px-3 py-2 text-sm outline-none data-[highlighted]:bg-accent"
                        ><SelectItemText>{{ role.name }}</SelectItemText
                        ><SelectItemIndicator
                            >✓</SelectItemIndicator
                        ></SelectItem
                    >
                </SelectViewport></SelectContent
            ></SelectPortal
        >
    </SelectRoot>
</template>
