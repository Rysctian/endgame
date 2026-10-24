<script setup lang="ts">
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from '@/components/ui/select';
import type { RoleOption } from './types';
defineProps<{ options: RoleOption[]; id?: string }>();
const model = defineModel<string>({ default: '' });
</script>
<template>
    <Select v-model="model"
        ><SelectTrigger :id="id" aria-label="Workspace role"
            ><SelectValue placeholder="Choose a role" /></SelectTrigger
        ><SelectContent
            ><SelectItem
                v-for="option in options"
                :key="option.id"
                :value="String(option.id)"
                >{{ option.name }}</SelectItem
            ></SelectContent
        ></Select
    >
</template>
