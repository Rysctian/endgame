<script setup lang="ts">
import type { PermissionModule } from './types';
defineProps<{ modules: PermissionModule[]; editable: boolean }>();
const permissions = defineModel<string[]>({ required: true });
const columns = ['view', 'create', 'edit', 'delete', 'setup'];
</script>
<template>
    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
            <caption class="sr-only">
                Module permissions
            </caption>
            <thead class="border-b bg-muted/40">
                <tr>
                    <th scope="col">Module</th>
                    <th
                        v-for="action in columns"
                        :key="action"
                        scope="col"
                        class="text-center capitalize"
                    >
                        {{ action.charAt(0).toUpperCase() + action.slice(1) }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="module in modules" :key="module.key">
                    <th scope="row" class="whitespace-nowrap text-foreground">
                        {{ module.label }}
                    </th>
                    <td
                        v-for="action in columns"
                        :key="action"
                        class="text-center"
                    >
                        <template v-if="module.actions[action]">
                            <input
                                v-if="
                                    editable && module.actions[action].canGrant
                                "
                                v-model="permissions"
                                type="checkbox"
                                :value="module.actions[action].key"
                                :aria-label="
                                    module.label +
                                    ': ' +
                                    module.actions[action].label
                                "
                                :title="module.actions[action].label"
                            />
                            <span
                                v-else
                                :aria-label="
                                    module.actions[action].label +
                                    (permissions.includes(
                                        module.actions[action].key,
                                    )
                                        ? ' granted'
                                        : ' not granted')
                                "
                                >{{
                                    permissions.includes(
                                        module.actions[action].key,
                                    )
                                        ? '✓'
                                        : '—'
                                }}</span
                            >
                        </template>
                        <span
                            v-else
                            class="text-muted-foreground"
                            aria-label="Not applicable"
                            >—</span
                        >
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
