<script setup lang="ts">
defineProps<{ errors: Record<string, string | undefined> }>();
</script>
<template>
    <div
        v-if="Object.keys(errors).length"
        role="alert"
        class="space-y-1 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
    >
        <p v-for="(error, key) in errors" :key="key">{{ error }}</p>
    </div>
</template>
