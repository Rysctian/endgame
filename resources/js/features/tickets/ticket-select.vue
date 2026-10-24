<script setup lang="ts">
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from '@/components/ui/select';
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
    <Select v-model="model" :disabled="disabled"
        ><SelectTrigger :id="id" :aria-label="label" class="ticket-control"
            ><SelectValue :placeholder="placeholder ?? label" /></SelectTrigger
        ><SelectContent
            ><SelectItem
                v-for="option in options"
                :key="option.value"
                :value="String(option.value)"
                >{{ option.label }}</SelectItem
            ></SelectContent
        ></Select
    >
</template>
