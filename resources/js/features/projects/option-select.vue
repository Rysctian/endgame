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
    id?: string;
}>();
const model = defineModel<string>({ default: '' });
</script>
<template>
    <Select v-model="model"
        ><SelectTrigger :id="id" :aria-label="label"
            ><SelectValue
                :placeholder="
                    placeholder ?? 'Choose an option'
                " /></SelectTrigger
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
